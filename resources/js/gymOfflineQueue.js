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

function isPermanentFailure(error) {
    // No response at all (network down, timeout) is transient — keep retrying.
    // A response with a 4xx status means the server actively rejected the
    // request (e.g. this set no longer belongs to the current session, or
    // failed validation) — retrying it will never succeed, so drop it rather
    // than blocking every later queued update behind it forever.
    const status = error?.response?.status;
    return typeof status === 'number' && status >= 400 && status < 500;
}

export async function drainQueue() {
    let queue = readQueue();

    while (queue.length > 0) {
        const item = queue[0];

        try {
            await window.axios.patch(`/gym-sets/${item.gymSetId}`, item.payload);
        } catch (error) {
            if (isPermanentFailure(error)) {
                // Drop poison entries so they don't wedge the queue forever.
                queue = readQueue().slice(1);
                writeQueue(queue);
                continue;
            }
            return;
        }

        // Re-read before removing, so anything enqueued during the await is preserved.
        queue = readQueue().slice(1);
        writeQueue(queue);
    }
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
