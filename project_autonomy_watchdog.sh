#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="/Users/matheusbrito/tuf/agendae"
STATE_DIR="${TMPDIR:-/private/tmp}/agendae-autonomy-watchdog"
LAST_REPORT_FILE="$STATE_DIR/last_report_epoch"
SCREENSHOT_DIR="$STATE_DIR/screenshots"
REPORT_FILE="$STATE_DIR/latest_report.txt"
INTERVAL_SECONDS=180
ANTIGRAVITY_AGENT="Antigravity"
ADMIN_PORTAL="E2E Portal Admin"
CLIENT_PORTAL="E2E Portal Client"

mkdir -p "$STATE_DIR" "$SCREENSHOT_DIR"

log() {
    printf '%s %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

maestri_cmd() {
    if command -v maestri >/dev/null 2>&1; then
        maestri "$@"
        return $?
    fi

    if [[ -n "${MAESTRI_CLI:-}" && -x "${MAESTRI_CLI:-}" ]]; then
        "$MAESTRI_CLI" "$@"
        return $?
    fi

    return 127
}

connected_agents() {
    maestri_cmd list 2>/dev/null | awk -F'"' '
        /^  - name: "/ {
            print $2
        }
    '
}

collect_agent_reports() {
    local agent report

    while IFS= read -r agent; do
        [ -n "$agent" ] || continue
        report="$(maestri_cmd check "$agent" 2>/dev/null || true)"
        printf '### AGENT: %s\n%s\n\n' "$agent" "$report"
    done
}

capture_portals() {
    local portal screenshot_path

    for portal in "$ADMIN_PORTAL" "$CLIENT_PORTAL"; do
        screenshot_path="$(maestri_cmd portal screenshot "$portal" 2>/dev/null || true)"
        printf '%s\t%s\n' "$portal" "$screenshot_path"
    done
}

is_inactive_output() {
    local output="$1"

    if [ -z "$output" ]; then
        return 0
    fi

    printf '%s' "$output" | grep -Ei '(inactive|inativo|idle|stopped|offline|disconnected|parado)' >/dev/null 2>&1
}

should_send_report() {
    local now="$1"
    local last_report="$2"
    local antigravity_check="$3"
    local elapsed=0

    if [ "$last_report" -gt 0 ]; then
        elapsed=$((now - last_report))
    else
        elapsed=$INTERVAL_SECONDS
    fi

    if [ "$elapsed" -ge "$INTERVAL_SECONDS" ]; then
        return 0
    fi

    if is_inactive_output "$antigravity_check"; then
        return 0
    fi

    return 1
}

build_report() {
    local now="$1"
    local last_report="$2"
    local antigravity_check="$3"
    local elapsed=0
    local portal_lines

    if [ "$last_report" -gt 0 ]; then
        elapsed=$((now - last_report))
    fi

    portal_lines="$(capture_portals)"

    {
        printf 'WATCHDOG AUTONOMY REPORT\n'
        printf 'Generated at: %s\n' "$(date '+%Y-%m-%d %H:%M:%S %Z')"
        printf 'Project root: %s\n' "$ROOT_DIR"
        printf 'Antigravity inactivity check: %s\n' "$(if is_inactive_output "$antigravity_check"; then printf 'inactive-or-stale'; else printf 'active-or-recent'; fi)"
        printf 'Elapsed since last report: %s seconds\n' "$elapsed"
        printf '\n== ANTIGRAVITY LATEST OUTPUT ==\n'
        if [ -n "$antigravity_check" ]; then
            printf '%s\n' "$antigravity_check"
        else
            printf '(no output)\n'
        fi
        printf '\n== PORTAL SCREENSHOTS ==\n'
        printf '%s\n' "$portal_lines" | while IFS="$(printf '\t')" read -r portal_path screenshot_path; do
            [ -n "$portal_path" ] || continue
            printf '- %s: %s\n' "$portal_path" "${screenshot_path:-<no screenshot returned>}"
        done
        printf '\n== AGENT REPORTS ==\n'
        collect_agent_reports
    } | tee "$REPORT_FILE"
}

send_report_to_antigravity() {
    local report_body prompt
    report_body="$(cat "$REPORT_FILE")"

    prompt="$(printf '%s\n\n%s\n\n%s\n' \
        "Qual o estado atual do projeto? O que precisa ser ajustado ou melhorado? Analise os prints e o status dos agentes para guiar o time com excelência contínua." \
        "Relatório consolidado:" \
        "$report_body")"

    maestri_cmd ask "$ANTIGRAVITY_AGENT" "$prompt"
}

main() {
    local last_report now antigravity_check
    local agents_output

    trap 'log "Watchdog interrompido."; exit 0' INT TERM

    agents_output="$(connected_agents || true)"
    if [ -z "$agents_output" ]; then
        log "Nenhum agente conectado foi encontrado pelo maestri."
    else
        log "Agentes monitorados:"
        printf '%s\n' "$agents_output" | while IFS= read -r agent; do
            [ -n "$agent" ] && printf ' - %s\n' "$agent"
        done
    fi

    while true; do
        now="$(date +%s)"
        last_report=0
        if [ -f "$LAST_REPORT_FILE" ]; then
            last_report="$(cat "$LAST_REPORT_FILE" 2>/dev/null || printf '0')"
        fi

        antigravity_check="$(maestri_cmd check "$ANTIGRAVITY_AGENT" 2>/dev/null || true)"

        if should_send_report "$now" "$last_report" "$antigravity_check"; then
            log "Condição atendida: inatividade detectada ou janela de 3 minutos expirada. Gerando relatório."
            build_report "$now" "$last_report" "$antigravity_check"
            send_report_to_antigravity
            printf '%s\n' "$now" > "$LAST_REPORT_FILE"
            log "Relatório enviado para $ANTIGRAVITY_AGENT."
        else
            log "Antigravity ativo e janela de 3 minutos ainda não expirou."
        fi

        sleep "$INTERVAL_SECONDS"
    done
}

main "$@"
