import { FileX } from "lucide-react";

interface ViewUnsupportedProps {
    readonly extension: string | null;
}

export default function ViewUnsupported({ extension }: Readonly<ViewUnsupportedProps>) {
    return (
        <div className="flex flex-col items-center justify-center h-full gap-3 text-base-content/50">
            <FileX size={48} />
            <p className="text-lg font-medium">Preview not available</p>
            {extension && (
                <p className="text-sm">
                    <span className="badge badge-ghost">.{extension}</span> files cannot be previewed.
                </p>
            )}
        </div>
    );
}
