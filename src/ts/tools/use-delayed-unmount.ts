import { useEffect, useRef, useState } from 'react';

export function useDelayedUnmount(value: boolean, delay: number): boolean {
  const [mounted, setMounted] = useState(value);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  if (value && !mounted) {
    setMounted(true);
  }

  useEffect(() => {
    if (!value) {
      timeoutRef.current = setTimeout(() => setMounted(false), delay);
      return () => {
        if (timeoutRef.current) {
          clearTimeout(timeoutRef.current);
        }
      };
    }
  }, [value, delay]);

  return mounted;
}
