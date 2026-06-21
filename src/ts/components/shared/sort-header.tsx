import { ArrowDown, ArrowUp, ArrowUpDown } from "lucide-react";

interface SortHeaderProps {
  label: string;
  direction: 'asc' | 'desc' | null;
  onClick: () => void;
}

export function SortHeader({ label, direction, onClick }: Readonly<SortHeaderProps>) {
  return (
    <div className="table-cell p-2 sticky top-0 bg-base-100 z-10 border-b border-base-200 cursor-pointer select-none" onClick={onClick}>
      <div className="flex items-center gap-1">
        {label}
        {direction === 'asc' && <ArrowUp size={14} />}
        {direction === 'desc' && <ArrowDown size={14} />}
        {!direction && <ArrowUpDown size={14} className="opacity-40" />}
      </div>
    </div>
  );
}
