import { Path } from "@ts/types";

export type ClipboardEntry = Path[];

export class ClipboardService {
  private readonly clipboardErrorMessage = 'Clipboard operations are not supported in this environment.';
  private readonly history: ClipboardEntry[] = [];
  private readonly isClipboardSupported: boolean;
  private readonly clipboard: Clipboard | undefined = undefined;
  private readonly maxHistorySize = 100;
  private isConsumingMode = false;

  constructor() {
    this.isClipboardSupported = !!globalThis.navigator.clipboard;
    if (this.isClipboardSupported) {
      this.clipboard = navigator.clipboard;
    } else {
      console.warn(this.clipboardErrorMessage);
    }
  }

  public setConsumingMode(isConsumingMode: boolean): void {
    this.isConsumingMode = isConsumingMode;
  }

  public getIsConsumingMode(): boolean {
    return this.isConsumingMode;
  }

  public getEntries(): Array<ClipboardEntry> {
    const entries = this.history.map(historyValue => this.cloneEntry(historyValue));

    if (this.isConsumingMode) {
      this.clearEntries();
    }

    return entries;
  }

  public getLastEntry(): ClipboardEntry | null {
    const lastEntry = this.history.at(-1);

    if (lastEntry === undefined) {
      return null;
    }

    const cloned = this.cloneEntry(lastEntry);

    if (this.isConsumingMode) {
      this.removeLastEntry();
    }

    return cloned;
  }

  public addEntry(...entry: ClipboardEntry): void {
    this.putInClipboard(entry);
  }

  public removeLastEntry(): void {
    this.removeLastFromClipboard();
  }

  public clearEntries(): void {
    this.history.length = 0;
    this.clearSystemClipboard();
  }

  private putInClipboard(entry: ClipboardEntry): void {
    if (this.history.length >= this.maxHistorySize) {
      this.history.shift();
    }

    this.history.push(entry);

    if (this.isClipboardSupported) {
      this.clipboard?.writeText(entry.join('\n')).catch(err => {
        console.error(this.clipboardErrorMessage, err);
      });
    }
  }

  private removeLastFromClipboard(): void {
    const poppedEntry = this.history.pop();

    if (!poppedEntry) {
      return;
    }

    const newLast = this.history.at(-1);

    if (newLast) {
      this.writeToSystemClipboard(newLast.join('\n'));
    } else {
      this.clearSystemClipboard();
    }
  }

  private clearSystemClipboard(): void {
    this.writeToSystemClipboard('');
  }

  private writeToSystemClipboard(text: string): void {
    if (this.isClipboardSupported) {
      this.clipboard?.writeText(text).catch(err => {
        console.error(this.clipboardErrorMessage, err);
      });
    }
  }

  private cloneEntry(entry: ClipboardEntry): ClipboardEntry {
    return Array.from(entry.map(path => `${path}` as Path));
  }
}
