import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useRef } from 'react';

interface DemoUser {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface QuickLoginSelectorProps {
    demoUsers?: DemoUser[];
    onUserSelect: (email: string, password: string) => void;
}

export default function QuickLoginSelector({
    demoUsers = [],
    onUserSelect,
}: QuickLoginSelectorProps) {
    const { isDemoEnvironment } = usePage<SharedData>().props;

    if (!isDemoEnvironment || demoUsers.length === 0) {
        return null;
    }

    const handleSelect = (userId: string) => {
        const user = demoUsers.find((u) => u.id === Number(userId));
        if (user) {
            onUserSelect(user.email, 'password');
        }
    };

    return (
        <div className="grid gap-2">
            <Label htmlFor="quick-login">Quick Login (Demo)</Label>
            <Select onValueChange={handleSelect}>
                <SelectTrigger id="quick-login">
                    <SelectValue placeholder="Select a user to auto-fill credentials" />
                </SelectTrigger>
                <SelectContent>
                    {demoUsers.map((user) => (
                        <SelectItem key={user.id} value={user.id.toString()}>
                            <div className="flex flex-col">
                                <span className="font-medium">{user.name}</span>
                                <span className="text-xs text-muted-foreground">
                                    {user.email} • {user.role}
                                </span>
                            </div>
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

