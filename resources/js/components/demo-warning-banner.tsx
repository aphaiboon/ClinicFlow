import { Alert, AlertDescription } from '@/components/ui/alert';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';

export default function DemoWarningBanner() {
    const { isDemoEnvironment } = usePage<SharedData>().props;

    if (!isDemoEnvironment) {
        return null;
    }

    return (
        <Alert
            className="fixed top-0 left-0 right-0 z-50 rounded-none border-x-0 border-t-0 border-b bg-yellow-50 dark:bg-yellow-950/20 border-yellow-200 dark:border-yellow-800 text-yellow-900 dark:text-yellow-200 [&>svg]:text-yellow-600 dark:[&>svg]:text-yellow-400"
            role="alert"
        >
            <AlertTriangle className="size-4" />
            <AlertDescription className="col-start-2 text-center text-sm font-medium">
                This is a testing platform. All data will be wiped daily at 12:00 AM
                PST.
            </AlertDescription>
        </Alert>
    );
}

