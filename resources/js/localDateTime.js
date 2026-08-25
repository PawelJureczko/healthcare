// Build local-time date/datetime strings for <input type="date"> and
// <input type="datetime-local"> prefills.
//
// `new Date().toISOString()` always returns UTC, which drifts from the
// user's real local calendar date/time near midnight (Poland is UTC+1/+2).
// These helpers build the string from the local Date getters instead.

const pad = (n) => String(n).padStart(2, '0');

export function localDate(date = new Date()) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

export function localDateTime(date = new Date()) {
    return `${localDate(date)}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
