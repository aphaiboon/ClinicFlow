import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import {
    ChevronDown,
    Search,
    Shield,
    Stethoscope,
    User,
    UserCheck,
    UserCog,
    UserPlus,
} from 'lucide-react';
import { useState, useMemo, useRef, useEffect } from 'react';

interface DemoUser {
    id: number;
    name: string;
    email: string;
    role: string;
    organizationRole?: string | null;
    organizationName?: string | null;
    type?: 'user' | 'patient';
}

interface QuickLoginSelectorProps {
    demoUsers?: DemoUser[];
    onUserSelect: (email: string, password: string) => void;
    onPatientSelect?: (email: string) => void;
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

const getRoleIcon = (role: string, orgRole?: string | null, type?: string) => {
    if (type === 'patient') {
        return User;
    }
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

const formatRole = (role: string, orgRole?: string | null, type?: string): string => {
    if (type === 'patient') {
        return 'Patient';
    }
    if (role === 'super_admin') {
        return 'Super Admin';
    }
    if (orgRole) {
        return orgRole.charAt(0).toUpperCase() + orgRole.slice(1);
    }
    return 'User';
};

function getDefaultUsersByRole(users: DemoUser[]): DemoUser[] {
    const roleMap = new Map<string, DemoUser>();

    users.forEach((user) => {
        const roleKey = `${user.type}-${user.role}-${user.organizationRole || 'none'}`;
        if (!roleMap.has(roleKey)) {
            roleMap.set(roleKey, user);
        }
    });

    return Array.from(roleMap.values()).sort((a, b) => a.id - b.id);
}

function filterUsers(users: DemoUser[], query: string): DemoUser[] {
    if (!query.trim()) {
        return getDefaultUsersByRole(users);
    }

    const lowerQuery = query.toLowerCase();
    return users
        .filter(
            (user) =>
                user.name.toLowerCase().includes(lowerQuery) ||
                user.email.toLowerCase().includes(lowerQuery) ||
                formatRole(user.role, user.organizationRole, user.type)
                    .toLowerCase()
                    .includes(lowerQuery) ||
                (user.organizationName?.toLowerCase().includes(lowerQuery) ??
                    false),
        )
        .sort((a, b) => a.id - b.id);
}

export default function QuickLoginSelector({
    demoUsers = [],
    onUserSelect,
    onPatientSelect,
}: QuickLoginSelectorProps) {
    const { isDemoEnvironment } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const triggerWrapperRef = useRef<HTMLDivElement>(null);
    const [triggerWidth, setTriggerWidth] = useState<number | undefined>(undefined);
    const [maxHeight, setMaxHeight] = useState<number | undefined>(undefined);

    useEffect(() => {
        if (triggerWrapperRef.current) {
            setTriggerWidth(triggerWrapperRef.current.offsetWidth);
        }
    }, []);

    // Calculate available space when popover opens
    useEffect(() => {
        if (open && triggerWrapperRef.current) {
            const rect = triggerWrapperRef.current.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const spaceBelow = viewportHeight - rect.bottom;
            const spaceAbove = rect.top;
            const collisionPadding = 16;
            const minHeight = 200; // Minimum usable height

            // Calculate max height based on available space
            // Prefer space below, but use space above if more available
            const availableSpace =
                Math.max(spaceBelow, spaceAbove) - collisionPadding;
            const calculatedMaxHeight = Math.max(
                minHeight,
                Math.min(400, availableSpace),
            );

            setMaxHeight(calculatedMaxHeight);
        } else {
            setMaxHeight(undefined);
        }
    }, [open]);

    if (!isDemoEnvironment || demoUsers.length === 0) {
        return null;
    }

    const filteredUsers = useMemo(
        () => filterUsers(demoUsers, searchQuery),
        [demoUsers, searchQuery],
    );

    const handleSelect = (user: DemoUser) => {
        if (user.type === 'patient' && onPatientSelect) {
            onPatientSelect(user.email);
        } else {
            onUserSelect(user.email, 'password');
        }
        setOpen(false);
        setSearchQuery('');
    };

    return (
        <div className="grid gap-3 rounded-lg border-2 border-dashed border-primary/30 bg-primary/5 p-4">
            <div className="flex items-center gap-2">
                <UserCheck className="size-4 text-primary" />
                <Label htmlFor="quick-login" className="text-sm font-semibold">
                    Quick Login (Demo)
                </Label>
            </div>
            <div ref={triggerWrapperRef} className="w-full">
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <Button
                            id="quick-login"
                            variant="outline"
                            className="h-11 w-full justify-between border-2 border-primary/20 bg-background font-medium shadow-sm hover:border-primary/40 focus:border-primary"
                        >
                            <span className="truncate text-muted-foreground">
                                Select a user to auto-fill credentials
                            </span>
                            <ChevronDown className="ml-2 size-4 shrink-0 opacity-50" />
                        </Button>
                    </PopoverTrigger>
                <PopoverContent
                    className="p-0"
                    style={{
                        width: triggerWidth ? `${triggerWidth}px` : undefined,
                        maxHeight: maxHeight ? `${maxHeight}px` : undefined,
                    }}
                    align="start"
                    side="bottom"
                    sideOffset={4}
                    collisionPadding={16}
                    avoidCollisions={true}
                >
                    <div className="p-2">
                        <div className="relative">
                            <Search className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                            <Input
                                placeholder="Search by name, email, role, or organization..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-8"
                                autoFocus
                            />
                        </div>
                    </div>
                    <div
                        className="overflow-auto"
                        style={{
                            maxHeight: maxHeight
                                ? `${maxHeight - 80}px`
                                : '400px',
                        }}
                    >
                        {filteredUsers.length === 0 ? (
                            <div className="p-4 text-center text-sm text-muted-foreground">
                                No users found
                            </div>
                        ) : (
                            filteredUsers.map((user) => {
                                const RoleIcon = getRoleIcon(
                                    user.role,
                                    user.organizationRole,
                                    user.type,
                                );
                                const roleLabel = formatRole(
                                    user.role,
                                    user.organizationRole,
                                    user.type,
                                );
                                return (
                                    <button
                                        key={user.id}
                                        type="button"
                                        onClick={() => handleSelect(user)}
                                        className="w-full cursor-pointer px-2 py-3 text-left transition-colors hover:bg-accent focus:bg-accent focus:outline-none"
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
                                    </button>
                                );
                            })
                        )}
                    </div>
                </PopoverContent>
                </Popover>
            </div>
        </div>
    );
}
