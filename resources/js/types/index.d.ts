import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface Organization {
    id: number;
    name: string;
    email?: string | null;
    is_active: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth & {
        currentOrganization?: Organization | null;
        organizations?: Organization[];
    };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    role?: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Patient {
    id: number;
    medical_record_number: string;
    first_name: string;
    last_name: string;
    date_of_birth: string;
    gender: string;
    phone?: string;
    email?: string;
    address_line_1?: string;
    address_line_2?: string;
    city?: string;
    state?: string;
    postal_code?: string;
    country?: string;
    appointments?: Appointment[];
    created_at: string;
    updated_at: string;
}

export interface Appointment {
    id: number;
    patient_id: number;
    user_id: number;
    exam_room_id?: number;
    appointment_date: string;
    appointment_time: string;
    duration_minutes: number;
    appointment_type: string;
    status: string;
    notes?: string;
    cancelled_at?: string;
    cancellation_reason?: string;
    patient?: Patient;
    user?: User;
    examRoom?: ExamRoom;
    created_at: string;
    updated_at: string;
}

export interface ExamRoom {
    id: number;
    room_number: string;
    name: string;
    floor?: number;
    equipment?: string[];
    capacity: number;
    is_active: boolean;
    notes?: string;
    appointments?: Appointment[];
    created_at: string;
    updated_at: string;
}

export interface AuditLog {
    id: number;
    user_id: number;
    action: string;
    resource_type: string;
    resource_id: number;
    changes?: Record<string, unknown>;
    ip_address?: string;
    user_agent?: string;
    metadata?: Record<string, unknown>;
    user?: User;
    created_at: string;
}
