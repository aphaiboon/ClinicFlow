import { Badge } from '@/components/ui/badge';
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
import {
    Shield,
    Stethoscope,
    User,
    UserCheck,
    UserCog,
    UserPlus,
} from 'lucide-react';

interface DemoUser {
    id: number;
    name: string;
    email: string;
    role: string;
    organizationRole?: string | null;
    organizationName?: string | null;
}

interface QuickLoginSelectorProps {
    demoUsers?: DemoUser[];
    onUserSelect: (email: string, password: string) => void;
}

const getRoleBadgeVariant = (
    role: string,
    orgRole?: string | null,
): 'default' | 'secondary' | 'outline' => {
    if (role === 'super_admin') {
        return 'default';
    }
    if (orgRole === 'admin' || orgRole === 'owner') {
        return 'default';
    }
    return 'secondary';
};

const getRoleIcon = (role: string, orgRole?: string | null) => {
    if (role === 'super_admin') {
        return Shield;
    }
    if (orgRole === 'admin' || orgRole === 'owner') {
        return UserCog;
    }
    if (orgRole === 'clinician') {
        return Stethoscope;
    }
    if (orgRole === 'receptionist') {
        return UserPlus;
    }
    return User;
};

const formatRole = (role: string, orgRole?: string | null): string => {
    if (role === 'super_admin') {
        return 'Super Admin';
    }
    if (orgRole) {
        return orgRole.charAt(0).toUpperCase() + orgRole.slice(1);
    }
    return 'User';
};

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
        <div className="grid gap-3 rounded-lg border-2 border-dashed border-primary/30 bg-primary/5 p-4">
            <div className="flex items-center gap-2">
                <UserCheck className="size-4 text-primary" />
                <Label htmlFor="quick-login" className="text-sm font-semibold">
                    Quick Login (Demo)
                </Label>
            </div>
            <Select onValueChange={handleSelect}>
                <SelectTrigger
                    id="quick-login"
                    className="h-11 border-2 border-primary/20 bg-background font-medium shadow-sm hover:border-primary/40 focus:border-primary"
                >
                    <SelectValue placeholder="Select a user to auto-fill credentials" />
                </SelectTrigger>
                <SelectContent className="max-h-[400px]">
                    {demoUsers.map((user) => {
                        const RoleIcon = getRoleIcon(user.role, user.organizationRole);
                        const roleLabel = formatRole(user.role, user.organizationRole);
                        return (
                            <SelectItem
                                key={user.id}
                                value={user.id.toString()}
                                className="py-3"
                            >
                                <div className="flex w-full items-start gap-3">
                                    <div className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary ring-1 ring-primary/20">
                                        <RoleIcon className="size-4.5" />
                                    </div>
                                    <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge
                                                variant={getRoleBadgeVariant(
                                                    user.role,
                                                    user.organizationRole,
                                                )}
                                                className="shrink-0 text-xs font-medium"
                                            >
                                                {roleLabel}
                                            </Badge>
                                            <span className="font-semibold text-foreground">
                                                {user.name}
                                            </span>
                                        </div>
                                        <div className="flex flex-col gap-0.5">
                                            <span className="truncate text-xs leading-relaxed text-muted-foreground">
                                                {user.email}
                                            </span>
                                            {user.organizationName && (
                                                <span className="truncate text-xs text-muted-foreground/70">
                                                    {user.organizationName}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </SelectItem>
                        );
                    })}
                </SelectContent>
            </Select>
        </div>
    );
}

