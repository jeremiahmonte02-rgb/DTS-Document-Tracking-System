<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\DocumentRoute;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->hasPermission('documents.view-all')) {
            return true;
        }

        if ($user->hasPermission('documents.view-dept') && $user->department_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (!$user->hasPermission('documents.create')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->department_id !== null;
    }

    public function receive(User $user, Document $document): bool
    {
        if (!$user->hasPermission('documents.receive')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->department_id) {
            return false;
        }

        if (in_array($document->status, ['completed', 'rejected'])) {
            return false;
        }

        $currentRouteStep = DocumentRoute::where('document_id', $document->id)
            ->where('status', 'current')
            ->first();

        if (!$currentRouteStep) {
            return false;
        }

        return (int) $currentRouteStep->department_id === (int) $user->department_id;
    }

    public function complete(User $user, Document $document): bool
    {
        if (!$user->hasPermission('documents.complete')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->department_id) {
            return false;
        }

        if ($document->status === 'completed') {
            return false;
        }

        $lastRouteStep = DocumentRoute::where('document_id', $document->id)
            ->orderBy('route_order', 'desc')
            ->first();

        if (!$lastRouteStep) {
            return false;
        }

        return (int) $lastRouteStep->department_id === (int) $user->department_id;
    }

    public function reject(User $user, Document $document): bool
    {
        if (!$user->hasPermission('documents.reject')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->department_id) {
            return false;
        }

        if (in_array($document->status, ['completed', 'rejected', 'cancelled'])) {
            return false;
        }

        $currentRouteStep = DocumentRoute::where('document_id', $document->id)
            ->where('status', 'current')
            ->first();

        if (!$currentRouteStep) {
            return false;
        }

        return (int) $currentRouteStep->department_id === (int) $user->department_id;
    }

    public function cancel(User $user, Document $document): bool
    {
        if (!$user->hasPermission('documents.cancel')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->department_id) {
            return false;
        }

        if (in_array($document->status, ['completed', 'cancelled'])) {
            return false;
        }

        return (int) $document->sender_department_id === (int) $user->department_id;
    }

    public function reportIssue(User $user, Document $document): bool
    {
        if (!$user->hasPermission('documents.report-issue')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->department_id !== null;
    }
}
