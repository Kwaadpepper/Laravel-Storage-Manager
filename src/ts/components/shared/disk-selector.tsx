import { useContainer } from '@ts/container';
import { useConfigStore, useDiskStore } from '@ts/stores';
import { Disk } from '@ts/types';
import { HardDrive } from 'lucide-react';

interface DiskSelectorProps {
  readonly className?: string
}

export default function DiskSelector({ className = '' }: Readonly<DiskSelectorProps>) {
  const { diskService, navigationService } = useContainer().cradle
  const { disks } = useConfigStore()
  const { currentDisk } = useDiskStore()

  if (disks.length <= 1) return null

  async function onDiskChange(disk: Disk) {
    await diskService.selectDisk(disk)
    navigationService.switchDisk()
  }

  return (
    <label className={`flex items-center gap-1 ${className}`}>
      <HardDrive size={14} className="shrink-0 text-base-content/60" aria-hidden="true" />
      <select
        className="select select-xs"
        value={currentDisk ?? ''}
        onChange={(e) => void onDiskChange(e.target.value as Disk)}
        aria-label="Storage disk"
      >
        {disks.map((disk) => (
          <option key={disk} value={disk}>{disk}</option>
        ))}
      </select>
    </label>
  )
}
