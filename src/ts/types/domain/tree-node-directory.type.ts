import { Path, TreeNode } from '@ts/types';

export interface TreeNodeDirectory extends TreeNode {
  path: Path
  name: string
  hasSubDirectories: boolean
}
