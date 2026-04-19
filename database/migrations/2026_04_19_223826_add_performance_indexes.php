<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    private function addIndex(string $table, array $columns, ?string $name = null): void
    {
        $indexName = $name ?? $table . '_' . implode('_', $columns) . '_index';
        $cols = implode('`, `', $columns);

        try {
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$cols}`)");
        } catch (\Throwable $e) {
            // Index already exists — skip
        }
    }

    private function dropIndex(string $table, string $name): void
    {
        try {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        } catch (\Throwable $e) {
            // Index doesn't exist — skip
        }
    }

    public function up(): void
    {
        // procedures
        $this->addIndex('procedures', ['status']);
        $this->addIndex('procedures', ['availment_date']);
        $this->addIndex('procedures', ['approval_code']);
        $this->addIndex('procedures', ['validation_requested']);
        $this->addIndex('procedures', ['status', 'clinic_id']);
        $this->addIndex('procedures', ['status', 'availment_date']);
        $this->addIndex('procedures', ['member_id', 'service_id']);

        // members
        $this->addIndex('members', ['status']);
        $this->addIndex('members', ['card_number']);
        $this->addIndex('members', ['member_type']);
        $this->addIndex('members', ['account_id', 'status']);
        $this->addIndex('members', ['card_number', 'status']);

        // accounts
        $this->addIndex('accounts', ['account_status']);
        $this->addIndex('accounts', ['endorsement_status']);
        $this->addIndex('accounts', ['endorsement_type']);
        $this->addIndex('accounts', ['expiration_date']);
        $this->addIndex('accounts', ['account_status', 'endorsement_status']);
        $this->addIndex('accounts', ['account_status', 'expiration_date']);

        // clinics
        $this->addIndex('clinics', ['accreditation_status']);
        $this->addIndex('clinics', ['fee_approval']);

        // generated_soas
        $this->addIndex('generated_soas', ['request_status']);
        $this->addIndex('generated_soas', ['status']);
        $this->addIndex('generated_soas', ['clinic_id', 'request_status']);

        // fee_adjustment_requests
        $this->addIndex('fee_adjustment_requests', ['status']);

        // notifications
        $this->addIndex('notifications', ['read_at']);
        $this->addIndex('notifications', ['notifiable_id', 'read_at']);

        // activity_log
        $this->addIndex('activity_log', ['created_at']);
    }

    public function down(): void
    {
        $this->dropIndex('procedures', 'procedures_status_index');
        $this->dropIndex('procedures', 'procedures_availment_date_index');
        $this->dropIndex('procedures', 'procedures_approval_code_index');
        $this->dropIndex('procedures', 'procedures_validation_requested_index');
        $this->dropIndex('procedures', 'procedures_status_clinic_id_index');
        $this->dropIndex('procedures', 'procedures_status_availment_date_index');
        $this->dropIndex('procedures', 'procedures_member_id_service_id_index');

        $this->dropIndex('members', 'members_status_index');
        $this->dropIndex('members', 'members_card_number_index');
        $this->dropIndex('members', 'members_member_type_index');
        $this->dropIndex('members', 'members_account_id_status_index');
        $this->dropIndex('members', 'members_card_number_status_index');

        $this->dropIndex('accounts', 'accounts_account_status_index');
        $this->dropIndex('accounts', 'accounts_endorsement_status_index');
        $this->dropIndex('accounts', 'accounts_endorsement_type_index');
        $this->dropIndex('accounts', 'accounts_expiration_date_index');
        $this->dropIndex('accounts', 'accounts_account_status_endorsement_status_index');
        $this->dropIndex('accounts', 'accounts_account_status_expiration_date_index');

        $this->dropIndex('clinics', 'clinics_accreditation_status_index');
        $this->dropIndex('clinics', 'clinics_fee_approval_index');

        $this->dropIndex('generated_soas', 'generated_soas_request_status_index');
        $this->dropIndex('generated_soas', 'generated_soas_status_index');
        $this->dropIndex('generated_soas', 'generated_soas_clinic_id_request_status_index');

        $this->dropIndex('fee_adjustment_requests', 'fee_adjustment_requests_status_index');

        $this->dropIndex('notifications', 'notifications_read_at_index');
        $this->dropIndex('notifications', 'notifications_notifiable_id_read_at_index');

        $this->dropIndex('activity_log', 'activity_log_created_at_index');
    }
};
