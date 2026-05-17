import { Path, TreeNode } from '@ts/types';

export interface TreeNodeFile extends TreeNode {
  path: Path
  name: string
  size: number
  extension: string | null
}
