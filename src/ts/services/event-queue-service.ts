import { useActionStore } from "@ts/stores";
import { NavigationService } from "./navigation-service";

export type EventType = 'DELETE' | 'MOVE' | 'COPY' | 'RENAME' | 'CREATE';

export interface FileEvent {
  id: string;
  type: EventType;
  sourcePath: string;
  destinationPath?: string;
  execute: () => Promise<void>;
}

export class EventQueueService {
  private queue: FileEvent[] = [];
  private isProcessing: boolean = false;
  private queuedRefreshes = 0;

  constructor(private readonly navigationService: NavigationService) {}

  public push(event: FileEvent) {
    this.queue.push(event);
    
    // Register in ActionStore
    useActionStore.getState().addAction({
      id: event.id,
      type: event.type,
      sourcePath: event.sourcePath,
      destinationPath: event.destinationPath,
    });

    this.processQueue();
  }

  public pushBatch(events: FileEvent[]) {
    for (const event of events) {
      this.queue.push(event);
      useActionStore.getState().addAction({
        id: event.id,
        type: event.type,
        sourcePath: event.sourcePath,
        destinationPath: event.destinationPath,
      });
    }

    this.processQueue();
  }

  private async processQueue() {
    if (this.isProcessing) return;
    this.isProcessing = true;

    while (this.queue.length > 0) {
      const event = this.queue.shift();
      if (!event) break;

      const store = useActionStore.getState();
      try {
        await event.execute();
        store.markActionComplete(event.id, 'success');
      } catch (error: unknown) {
        store.markActionComplete(event.id, 'error', error instanceof Error ? error.message : 'Operation failed');
      }

      this.queuedRefreshes++;

      // If the queue is now empty, or we just finished a batch, we refresh the UI.
      // We can also throttle this if we want, but doing it at the end of the queue is usually best.
      if (this.queue.length === 0) {
        if (this.queuedRefreshes > 0) {
          this.navigationService.refreshCurrentPath();
          this.queuedRefreshes = 0;
        }
      }
    }

    this.isProcessing = false;
  }
}
