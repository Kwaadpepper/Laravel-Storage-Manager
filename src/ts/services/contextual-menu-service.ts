import { AnchorName, ContextualMenuEntry, ContextualMenuStore } from "@ts/stores";

export class ContextualMenuService {
  private readonly contextualMenuList: Map<AnchorName, ContextualMenuEntry[]> = new Map();

  constructor(
    private readonly contextualMenuStore: ContextualMenuStore,
  ) {}

  public register(anchor: AnchorName, entries: ContextualMenuEntry[]): void {
    this.contextualMenuList.set(anchor, [...entries]);
  }

  public unregister(anchor: AnchorName): void {
    this.contextualMenuList.delete(anchor);
  }

  public open(anchor: AnchorName): boolean {
    const entries = this.contextualMenuList.get(anchor) || [];
    if (entries.length === 0) {
      this.contextualMenuStore.closeMenu();
      return false;
    }

    this.contextualMenuStore.setMenu(anchor, entries);
    return true;
  }

  public close(): void {
    this.contextualMenuStore.closeMenu();
  }
}
