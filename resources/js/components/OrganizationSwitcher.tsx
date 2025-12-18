import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import * as organizationRoutes from '@/routes/organizations';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Building, Check } from 'lucide-react';

interface Organization {
    id: number;
    name: string;
}

interface Props {
    organizations?: Organization[];
    currentOrganization?: Organization | null;
}

export default function OrganizationSwitcher({
    organizations = [],
    currentOrganization,
}: Props) {
    const { auth } = usePage<SharedData>().props;

    if (!auth.user || organizations.length <= 1) {
        return null;
    }

    const handleSwitch = (organizationId: number) => {
        router.post(
            organizationRoutes.switch({ organization: organizationId }).url,
            {},
            {
                preserveScroll: true,
                preserveState: false,
            },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" className="w-full justify-start">
                    <Building className="mr-2 h-4 w-4" />
                    <span className="truncate">
                        {currentOrganization?.name || 'Select Organization'}
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-56">
                {organizations.map((org) => (
                    <DropdownMenuItem
                        key={org.id}
                        onClick={() => handleSwitch(org.id)}
                        className="flex items-center justify-between"
                    >
                        <span>{org.name}</span>
                        {currentOrganization?.id === org.id && (
                            <Check className="h-4 w-4" />
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
