export type Status = 'open' | 'in_progress' | 'resolved' | 'closed';
export type Priority = 'low' | 'medium' | 'high';
export interface Option {
  value: string;
  label: string;
}
export interface Assignee {
  id: number;
  name: string;
  active_count: number;
}
export interface Ticket {
  id: number;
  title: string;
  description: string;
  priority: Priority;
  status: Status;
  assignee_id: number;
  assignee: Assignee;
  created_at: string;
  updated_at: string;
}
export interface FormOptions {
  assignees: Assignee[];
  statuses: Option[];
  priorities: Option[];
}
export interface Filters {
  search?: string;
  status?: string;
  priority?: string;
  assignee_id?: string | number;
}
export interface PaginatedTickets {
  data: Ticket[];
  total: number;
  from: number | null;
  to: number | null;
  current_page: number;
  last_page: number;
  prev_page_url: string | null;
  next_page_url: string | null;
}
export const formatDate = (value: string, time = false) =>
  new Intl.DateTimeFormat('pt-BR', {
    timeZone: 'America/Sao_Paulo',
    day: '2-digit',
    month: 'short',
    year: time ? 'numeric' : undefined,
    ...(time ? ({ hour: '2-digit', minute: '2-digit' } as const) : {}),
  }).format(new Date(value));
export const ticketCode = (id: number) => `CH-${String(id).padStart(4, '0')}`;
export const initials = (name: string) =>
  name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('');
