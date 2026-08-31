<?php

namespace App\Enums;

enum ActivityCode: int
{
    // Auth (10-19)
    case AUTH_LOGIN = 10;
    case AUTH_LOGOUT = 11;

    // Documents (20-29)
    case DOC_CREATED = 20;
    case DOC_RECEIVED = 21;
    case DOC_COMPLETED = 22;
    case DOC_REJECTED = 23;
    case DOC_CANCELLED = 24;
    case DOC_ROUTE_UPDATED = 25;
    case DOC_ISSUE_REPORTED = 26;

    // Users (30-39)
    case USER_CREATED = 30;
    case USER_UPDATED = 31;
    case USER_TOGGLED = 32;

    // Departments (40-49)
    case DEPT_CREATED = 40;
    case DEPT_UPDATED = 41;
    case DEPT_TOGGLED = 42;
    case DEPT_SLA_UPDATED = 43;

    // Policies (50-59)
    case POLICY_UPDATED = 50;

    public function label(): string
    {
        return match ($this) {
            self::AUTH_LOGIN => 'Logged in',
            self::AUTH_LOGOUT => 'Logged out',
            self::DOC_CREATED => 'Created document',
            self::DOC_RECEIVED => 'Received document',
            self::DOC_COMPLETED => 'Completed document',
            self::DOC_REJECTED => 'Rejected document',
            self::DOC_CANCELLED => 'Cancelled document',
            self::DOC_ROUTE_UPDATED => 'Updated routing path',
            self::DOC_ISSUE_REPORTED => 'Reported issue',
            self::USER_CREATED => 'Created user',
            self::USER_UPDATED => 'Updated user',
            self::USER_TOGGLED => 'Toggled user status',
            self::DEPT_CREATED => 'Created department',
            self::DEPT_UPDATED => 'Updated department',
            self::DEPT_TOGGLED => 'Toggled department status',
            self::DEPT_SLA_UPDATED => 'Updated department SLAs',
            self::POLICY_UPDATED => 'Updated routing policy',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AUTH_LOGIN,
            self::AUTH_LOGOUT => 'bg-secondary',
            self::DOC_CREATED,
            self::DOC_RECEIVED,
            self::DOC_COMPLETED => 'bg-success',
            self::DOC_REJECTED,
            self::DOC_CANCELLED,
            self::DOC_ISSUE_REPORTED => 'bg-danger',
            self::DOC_ROUTE_UPDATED => 'bg-warning text-dark',
            self::USER_CREATED,
            self::USER_UPDATED,
            self::USER_TOGGLED => 'bg-info text-dark',
            self::DEPT_CREATED,
            self::DEPT_UPDATED,
            self::DEPT_TOGGLED,
            self::DEPT_SLA_UPDATED => 'bg-primary',
            self::POLICY_UPDATED => 'bg-dark',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AUTH_LOGIN => 'bi-box-arrow-in-right',
            self::AUTH_LOGOUT => 'bi-box-arrow-right',
            self::DOC_CREATED => 'bi-file-earmark-plus',
            self::DOC_RECEIVED => 'bi-qr-code-scan',
            self::DOC_COMPLETED => 'bi-check-circle',
            self::DOC_REJECTED => 'bi-x-circle',
            self::DOC_CANCELLED => 'bi-slash-circle',
            self::DOC_ROUTE_UPDATED => 'bi-diagram-3',
            self::DOC_ISSUE_REPORTED => 'bi-exclamation-triangle',
            self::USER_CREATED => 'bi-person-plus',
            self::USER_UPDATED => 'bi-person-gear',
            self::USER_TOGGLED => 'bi-person-toggle',
            self::DEPT_CREATED => 'bi-building-add',
            self::DEPT_UPDATED => 'bi-building-gear',
            self::DEPT_TOGGLED => 'bi-toggle-on',
            self::DEPT_SLA_UPDATED => 'bi-clock-history',
            self::POLICY_UPDATED => 'bi-shield-lock',
        };
    }

    public function category(): string
    {
        return match (true) {
            $this->value >= 10 && $this->value <= 19 => 'Auth',
            $this->value >= 20 && $this->value <= 29 => 'Documents',
            $this->value >= 30 && $this->value <= 39 => 'Users',
            $this->value >= 40 && $this->value <= 49 => 'Departments',
            $this->value >= 50 && $this->value <= 59 => 'Policies',
            default => 'Other',
        };
    }
}
