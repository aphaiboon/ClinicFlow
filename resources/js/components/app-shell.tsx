import DemoWarningBanner from '@/components/demo-warning-banner';
import { SidebarProvider } from '@/components/ui/sidebar';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

interface AppShellProps {
    children: React.ReactNode;
    variant?: 'header' | 'sidebar';
}

export function AppShell({ children, variant = 'header' }: AppShellProps) {
    const isOpen = usePage<SharedData>().props.sidebarOpen;
    const { isDemoEnvironment } = usePage<SharedData>().props;
    const bannerHeight = isDemoEnvironment ? '3.5rem' : '0px';

    // Inject CSS to fix sidebar positioning when using sidebar variant
    useEffect(() => {
        if (variant === 'sidebar' && isDemoEnvironment) {
            const styleId = 'banner-layout-fix';
            let style = document.getElementById(styleId) as HTMLStyleElement;

            if (!style) {
                style = document.createElement('style');
                style.id = styleId;
                document.head.appendChild(style);
            }

            style.textContent = `
                [data-slot="sidebar-wrapper"] div.fixed.inset-y-0 {
                    top: var(--banner-height, 3.5rem) !important;
                    height: calc(100svh - var(--banner-height, 3.5rem)) !important;
                }
                [data-slot="sidebar-inset"] {
                    min-height: calc(100svh - var(--banner-height, 3.5rem)) !important;
                }
            `;

            return () => {
                const existingStyle = document.getElementById(styleId);
                if (existingStyle) {
                    existingStyle.remove();
                }
            };
        }
    }, [variant, isDemoEnvironment]);

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">
                <DemoWarningBanner />
                <div className="flex-1" style={{ paddingTop: bannerHeight }}>
                    {children}
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col">
            <DemoWarningBanner />
            <SidebarProvider
                defaultOpen={isOpen}
                style={
                    {
                        '--banner-height': bannerHeight,
                    } as React.CSSProperties
                }
            >
                {children}
            </SidebarProvider>
        </div>
    );
}
