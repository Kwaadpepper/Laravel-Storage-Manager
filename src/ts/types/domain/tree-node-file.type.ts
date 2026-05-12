import { Path, TreeNode } from '@ts/types';

export interface TreeNodeFile extends TreeNode {
  path: Path
  size: number
  extension: string | null
}
