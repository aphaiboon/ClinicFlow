import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useCallback, useState } from 'react';

interface SearchInputProps {
    placeholder?: string;
    searchKey?: string;
    className?: string;
    defaultValue?: string;
}

export function SearchInput({
    placeholder = 'Search...',
    searchKey = 'search',
    className,
    defaultValue = '',
}: SearchInputProps) {
    const [value, setValue] = useState(defaultValue);

    const handleSubmit = useCallback(
        (e: React.FormEvent) => {
            e.preventDefault();
            router.get(
                window.location.pathname,
                { [searchKey]: value },
                {
                    preserveState: true,
                    replace: true,
                },
            );
        },
        [value, searchKey],
    );

    return (
        <form onSubmit={handleSubmit} className={className}>
            <div className="relative">
                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    placeholder={placeholder}
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    className="pl-9"
                />
            </div>
        </form>
    );
}
