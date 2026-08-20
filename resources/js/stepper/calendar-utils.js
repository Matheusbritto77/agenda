export function formatDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export function formatCurrency(value) {
    const num = parseFloat(value);
    if (isNaN(num)) return '0,00';
    return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function generateCalendarDays(currentDate, blockedSlots, selectedDateStr) {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDayIndex = new Date(year, month, 1).getDay();
    const lastDay = new Date(year, month + 1, 0).getDate();
    const prevLastDay = new Date(year, month, 0).getDate();

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const days = [];

    for (let x = firstDayIndex; x > 0; x--) {
        days.push({
            day: prevLastDay - x + 1,
            isOtherMonth: true,
            isDisabled: true,
            isBlocked: false,
            blockReason: '',
            fullDate: null
        });
    }

    for (let i = 1; i <= lastDay; i++) {
        const thisDate = new Date(year, month, i);
        const thisDateStr = formatDateString(thisDate);
        const isPast = thisDate < today;

        const matchingBlock = blockedSlots.find(b => {
            if (!b.starts_at || !b.ends_at) return false;
            const bStart = new Date(b.starts_at);
            const bEnd = new Date(b.ends_at);
            bStart.setHours(0, 0, 0, 0);
            bEnd.setHours(23, 59, 59, 999);
            return thisDate >= bStart && thisDate <= bEnd;
        });

        const isBlocked = !!matchingBlock;
        const blockReason = matchingBlock ? (matchingBlock.reason || 'Data Indisponível') : '';

        days.push({
            day: i,
            isOtherMonth: false,
            isToday: thisDate.getTime() === today.getTime(),
            isSelected: selectedDateStr === thisDateStr,
            isDisabled: isPast || isBlocked,
            isBlocked: isBlocked,
            blockReason: blockReason,
            fullDate: thisDateStr
        });
    }

    const totalSlots = days.length;
    const remaining = 35 - totalSlots;
    const extraNeeded = remaining < 0 ? 42 - totalSlots : remaining;
    for (let j = 1; j <= extraNeeded; j++) {
        days.push({
            day: j,
            isOtherMonth: true,
            isDisabled: true,
            isBlocked: false,
            blockReason: '',
            fullDate: null
        });
    }

    return days;
}
