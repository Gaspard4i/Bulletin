export enum Role {
  STUDENT = "STUDENT",
  TEACHER = "TEACHER",
  SCOLARITE = "SCOLARITE",
  ADMIN = "ADMIN",
}

export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  role: Role;
  avatarUrl?: string;
  createdAt: string;
  updatedAt: string;
}

export interface Subject {
  id: string;
  name: string;
  code: string;
  coefficient: number;
  teacherId: string;
}

export interface Grade {
  id: string;
  studentId: string;
  subjectId: string;
  value: number;
  coefficient: number;
  session: string;
  comment?: string;
  createdAt: string;
  updatedAt: string;
  subject?: Subject;
  student?: User;
}

export interface Bulletin {
  id: string;
  studentId: string;
  semester: string;
  academicYear: string;
  status: BulletinStatus;
  averageGrade?: number;
  grades: Grade[];
  comment?: string;
  validatedBy?: string;
  validatedAt?: string;
  createdAt: string;
  updatedAt: string;
  student?: User;
}

export enum BulletinStatus {
  DRAFT = "DRAFT",
  PENDING_VALIDATION = "PENDING_VALIDATION",
  VALIDATED = "VALIDATED",
  PUBLISHED = "PUBLISHED",
}

export interface AuthTokens {
  accessToken: string;
  refreshToken: string;
}

export interface LoginResponse {
  user: User;
  tokens: AuthTokens;
}

export interface ApiError {
  message: string;
  statusCode: number;
  errors?: Record<string, string[]>;
}
