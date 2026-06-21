import { Path, TreeNode } from '@ts/types';

export interface TreeNodeFile extends TreeNode {
  path: Path
  name: string
  size: number
  extension: string | null
  visibility: 'public' | 'private' | null
  publicUrl?: string | null
}

export function isFile(node: TreeNode): node is TreeNodeFile {
  return (node as TreeNodeFile).extension !== undefined
}
