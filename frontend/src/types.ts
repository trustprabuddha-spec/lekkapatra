export type StudentSource = 'anubhava' | 'central';

export interface StudentRecord {
  source: StudentSource;
  source_student_id: number;
  student_name: string;
  parent_name: string | null;
  parent_phone: string;
  parent_email: string;
  class_name: string;
  section: string;
  lifecycle_status: string;
}

export interface FeeType {
  id: number;
  school_code: string;
  name: string;
  category: string;
  default_amount: number;
  recurrence: 'one_time' | 'monthly' | 'termly' | 'yearly';
  is_active: number;
}
