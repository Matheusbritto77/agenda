#!/usr/bin/env bash

# Auto Prompt Answerer / Heuristics Tool for Maestri Agents
# Este script verifica os terminais dos outros agentes. Quando detecta um prompt de confirmacao/menu (ex: "Do you want to proceed?", "1. Yes"),
# ele notifica este agente (Antigravity) para decidir e responde automaticamente com a opcao recomendada ("1\n").

DEFAULT_AGENTS=(
    "Backend-Codex"
    "Backend-Codex-2"
    "Frontend-Antigravity"
    "Frontend-Antigravity-2"
    "Orchestrator"
    "Frontend-Architect"
    "Backend-Architect"
    "QA-E2E-Tester"
    "QA-Unit-Tester"
    "QA-Integration-Tester"
    "QA-Code-Tester-2"
    "QA-Human-Tester-1"
    "QA-Human-Tester-2"
    "Security-Advisor"
)

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
PROJECT_ROOT="${PROJECT_ROOT:-$SCRIPT_DIR}"

EXTRA_AGENTS_FILES=(
    "${HOME}/.maestri/prompt-monitor-agents.txt"
    "${PROJECT_ROOT}/.maestri/prompt-monitor-agents.txt"
)

read_extra_agents() {
    local file

    for file in "${EXTRA_AGENTS_FILES[@]}"; do
        [[ -f "$file" ]] || continue
        sed -e 's/#.*$//' -e '/^[[:space:]]*$/d' "$file" 2>/dev/null
    done
}

discover_agents() {
    local agents

    agents="$(
        {
            printf '%s\n' "${DEFAULT_AGENTS[@]}"
            read_extra_agents
            maestri list 2>/dev/null | awk '
            /^Connected agents:/ { in_agents = 1; next }
            /^Connected portals:/ { in_agents = 0 }
            in_agents && /name: "/ {
                sub(/^.*name: "/, "")
                sub(/".*$/, "")
                print
            }
        '
        } | awk 'NF && !seen[$0]++'
    )"

    if [[ -n "$agents" ]]; then
        printf '%s\n' "$agents"
        return 0
    fi

    printf '%s\n' "${DEFAULT_AGENTS[@]}"
}

LOG_DIR="${HOME}/.maestri/logs"
LOG_FILE="${LOG_DIR}/prompt-monitor.log"

mkdir -p "$LOG_DIR"

# Keep the monitor silent in the interactive terminal; write all runtime output to a local log.
exec >>"$LOG_FILE" 2>&1

echo "Starting Auto Prompt Monitor for Maestri Canvas Agents..."

AGENTS=()
while IFS= read -r line; do
    [[ -n "$line" ]] && AGENTS+=("$line")
done < <(discover_agents)

if [[ ${#AGENTS[@]} -gt 0 ]]; then
    echo "=== TERMINAIS CONECTADOS PARA MONITORAMENTO ==="
    printf ' - %s\n' "${AGENTS[@]}"
fi

while true; do
    AGENTS=()
    while IFS= read -r line; do
        [[ -n "$line" ]] && AGENTS+=("$line")
    done < <(discover_agents)

    for AGENT in "${AGENTS[@]}"; do
        OUTPUT=$(maestri check "$AGENT" 2>/dev/null)
        if echo "$OUTPUT" | grep -qE "(Would you like to run the following command|Would you like to make the following edits)"; then
            echo "[HEURISTICS] Prompt detected in agent terminal: $AGENT"

            # Envia '2\n' para permitir automaticamente execucao/edicao sugerida
            maestri ask "$AGENT" --raw "2\n" 2>/dev/null

            echo "[HEURISTICS] Responded with option 2 (Always allow) to $AGENT."
        elif echo "$OUTPUT" | grep -qE "(Do you want to proceed\?|Do you want to run|Select an option|> 1\.|1\. Yes)"; then
            echo "[HEURISTICS] Prompt detected in agent terminal: $AGENT"
            
            # Envia '1\n' para selecionar 'Yes' ou a primeira opcao padrao
            maestri ask "$AGENT" --raw "1\n" 2>/dev/null
            
            echo "[HEURISTICS] Responded with option 1 (Yes) to $AGENT."
        fi
    done
    sleep 3
done
