import { useActionStore, useDiskStore, type ActionStore } from '@ts/stores';
import { useNavigation } from './useNavigation';

export type EventType =
  | 'DELETE'
  | 'MOVE'
  | 'COPY'
  | 'RENAME'
  | 'CREATE'
  | 'ZIP'
  | 'UNZIP'
  | 'RESTORE'
  | 'PURGE';

export interface FileEvent {
  id: string;
  type: EventType;
  sourcePath: string;
  destinationPath?: string;
  execute: () => Promise<void>;
}

// Module-level shared queue for application components
const sharedQueue: FileEvent[] = [];
let sharedIsProcessing = false;
let sharedQueuedRefreshes = 0;

export function useEventQueue(
  injectedNav?: { refreshCurrentPath: () => Promise<void> | void },
  injectedActionStore?: ActionStore
) {
  const isIsolated = Boolean(injectedNav || injectedActionStore);
  const queue: FileEvent[] = isIsolated ? [] : sharedQueue;
  let localIsProcessing = false;
  let localQueuedRefreshes = 0;

  const getIsProcessing = () => (isIsolated ? localIsProcessing : sharedIsProcessing);
  const setIsProcessing = (val: boolean) => {
    if (isIsolated) localIsProcessing = val;
    else sharedIsProcessing = val;
  };
  const getQueuedRefreshes = () => (isIsolated ? localQueuedRefreshes : sharedQueuedRefreshes);
  const setQueuedRefreshes = (val: number) => {
    if (isIsolated) localQueuedRefreshes = val;
    else sharedQueuedRefreshes = val;
  };

  const getNav = () => injectedNav ?? useNavigation();
  const getActionStore = () => injectedActionStore ?? useActionStore();

  async function processQueue(): Promise<void> {
    if (getIsProcessing()) return;
    setIsProcessing(true);

    const actionStore = getActionStore();
    const nav = getNav();

    while (queue.length > 0) {
      const event = queue.shift();
      if (!event) break;

      try {
        await event.execute();
        actionStore.markActionComplete(event.id, 'success');
        setQueuedRefreshes(getQueuedRefreshes() + 1);
      } catch (error: unknown) {
        actionStore.markActionComplete(
          event.id,
          'error',
          error instanceof Error ? error.message : 'Operation failed'
        );
      }

      if (queue.length === 0) {
        if (getQueuedRefreshes() > 0) {
          void nav.refreshCurrentPath();
          try {
            const diskStore = useDiskStore();
            void diskStore.fetchDiskStats(undefined, true);
          } catch {
            /* ignore outside Pinia context */
          }
          setQueuedRefreshes(0);
        }
      }
    }

    setIsProcessing(false);
  }

  function push(event: FileEvent): void {
    queue.push(event);

    const actionStore = getActionStore();
    actionStore.addAction({
      id: event.id,
      type: event.type,
      sourcePath: event.sourcePath,
      destinationPath: event.destinationPath,
    });

    void processQueue();
  }

  function pushBatch(events: FileEvent[]): void {
    const actionStore = getActionStore();
    for (const event of events) {
      queue.push(event);
      actionStore.addAction({
        id: event.id,
        type: event.type,
        sourcePath: event.sourcePath,
        destinationPath: event.destinationPath,
      });
    }

    void processQueue();
  }

  async function pushAndWait(event: FileEvent): Promise<void> {
    return new Promise<void>((resolve, reject) => {
      push({
        ...event,
        execute: async (): Promise<void> => {
          try {
            await event.execute();
            resolve();
          } catch (error) {
            getActionStore().removeAction(event.id);
            reject(error);
          }
        },
      });
    });
  }

  return {
    push,
    pushBatch,
    pushAndWait,
  };
}

export class EventQueueService {
  private readonly queue: ReturnType<typeof useEventQueue>;

  constructor(
    nav?: { refreshCurrentPath: () => void },
    actionStore?: ActionStore,
    _diskService?: unknown
  ) {
    this.queue = useEventQueue(nav, actionStore);
  }

  public push(event: FileEvent): void {
    this.queue.push(event);
  }

  public pushBatch(events: FileEvent[]): void {
    this.queue.pushBatch(events);
  }

  public async pushAndWait(event: FileEvent): Promise<void> {
    return this.queue.pushAndWait(event);
  }
}
