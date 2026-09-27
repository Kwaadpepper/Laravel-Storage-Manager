import { createLucideIcon } from 'lucide-vue-next';

export const FilePdf = createLucideIcon('file-pdf', [
  [
    'path',
    {
      d: 'M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z',
      key: 'outline',
    },
  ],
  ['path', { d: 'M14 2v5a1 1 0 0 0 1 1h5', key: 'fold' }],
  ['path', { d: 'M7 18v-5h1.5a1.5 1.5 0 0 1 0 3H7', key: 'p' }],
  ['path', { d: 'M11.5 18v-5h1.2a2.5 2.5 0 0 1 0 5H11.5', key: 'd' }],
  ['path', { d: 'M16.5 18v-5h2', key: 'f1' }],
  ['path', { d: 'M16.5 15.5h1.5', key: 'f2' }],
]);

export default FilePdf;
