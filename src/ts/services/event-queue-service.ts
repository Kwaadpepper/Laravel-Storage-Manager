import { ActionStore } from "@ts/stores";
import { DiskService } from "./disk-service";
import { NavigationService } from "./navigation-service";

export type EventType = 'DELETE' | 'MOVE' | 'COPY' | 'RENAME' | 'CREATE' | 'ZIP' | 'UNZIP' | 'RESTORE' | 'PURGE';

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

  constructor(
    private readonly navigationService: NavigationService,
    private readonly actionStore: ActionStore,
    private readonly diskService?: DiskService,
  ) {}

  public push(event: FileEvent) {
    this.queue.push(event);

    this.actionStore.addAction({
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
      this.actionStore.addAction({
        id: event.id,
        type: event.type,
        sourcePath: event.sourcePath,
        destinationPath: event.destinationPath,
      });
    }

    this.processQueue();
  }

  public async pushAndWait(event: FileEvent): Promise<void> {
    return new Promise<void>((resolve, reject) => {
      this.push({
        ...event,
        execute: async (): Promise<void> => {
          try {
            await event.execute();
            resolve();
          } catch (error) {
            this.actionStore.removeAction(event.id);
            reject(error);
          }
        },
      });
    });
  }

  private async processQueue() {
    if (this.isProcessing) return;
    this.isProcessing = true;

    while (this.queue.length > 0) {
      const event = this.queue.shift();
      if (!event) break;

      try {
        await event.execute();
        this.actionStore.markActionComplete(event.id, 'success');
        this.queuedRefreshes++;
      } catch (error: unknown) {
        this.actionStore.markActionComplete(
          event.id,
          'error',
          error instanceof Error ? error.message : 'Operation failed'
        );
      }

      if (this.queue.length === 0) {
        if (this.queuedRefreshes > 0) {
          this.navigationService.refreshCurrentPath();
          void this.diskService?.fetchDiskStats(undefined, true);
          this.queuedRefreshes = 0;
        }
      }
    }

    this.isProcessing = false;
  }
}
