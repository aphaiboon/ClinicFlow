import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertTriangle, X } from 'lucide-react';
import { useState } from 'react';

export default function DemoInfoWidget() {
    const { isDemoEnvironment } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);

    if (!isDemoEnvironment) {
        return null;
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    size="icon"
                    className="fixed bottom-4 right-4 z-50 size-12 rounded-full bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 border-yellow-600 dark:border-yellow-700 shadow-lg"
                    aria-label="Demo environment information"
                >
                    <AlertTriangle className="size-5 text-yellow-900 dark:text-yellow-100" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-80 p-0"
                align="end"
                side="top"
                sideOffset={8}
            >
                <div className="relative">
                    <Alert className="m-0 rounded-lg border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-950/20 text-yellow-900 dark:text-yellow-200 [&>svg]:text-yellow-600 dark:[&>svg]:text-yellow-400">
                        <AlertTriangle className="size-4 shrink-0" />
                        <div className="flex-1 pr-8">
                            <AlertDescription className="text-sm font-medium leading-relaxed">
                                This is a testing platform. All data will be
                                wiped daily at 12:00 AM PST.
                            </AlertDescription>
                        </div>
                    </Alert>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="absolute top-3 right-3 h-6 w-6 text-yellow-900 dark:text-yellow-200 hover:bg-yellow-100 dark:hover:bg-yellow-900/40"
                        onClick={() => setOpen(false)}
                        aria-label="Close"
                    >
                        <X className="size-3" />
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}
