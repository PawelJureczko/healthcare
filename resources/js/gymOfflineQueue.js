// Offline-resilient queue for live-logger set updates. Optimistic UI
// updates the local state immediately; the PATCH to the backend goes
// through axios (already configured with CSRF handling in bootstrap.js).
// On network failure, the mutation is queued in localStorage and retried
// on the next 'online' event or the next call to drainQueue().

const QUEUE_KEY = 'gym-set-queue';

function readQueue() {
    try {
        return JSON.parse(localStorage.getItem(QUEUE_KEY)) ?? [];
    } catch {
        return [];
    }
}

function writeQueue(queue) {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
}

export function enqueueSetUpdate(gymSetId, payload) {
    const queue = readQueue();
    queue.push({ gymSetId, payload });
    writeQueue(queue);
}

export async function drainQueue() {
    const queue = readQueue();

    for (let i = 0; i < queue.length; i++) {
        try {
            await window.axios.patch(`/gym-sets/${queue[i].gymSetId}`, queue[i].payload);
        } catch {
            writeQueue(queue.slice(i));
            return;
        }
    }

    writeQueue([]);
}

export async function updateGymSet(gymSetId, payload) {
    try {
        await window.axios.patch(`/gym-sets/${gymSetId}`, payload);
        return true;
    } catch {
        enqueueSetUpdate(gymSetId, payload);
        return false;
    }
}
