<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE FUNCTION protect_journal_entry()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    IF OLD.status <> 'draft' THEN
                        RAISE EXCEPTION
                            'Posted or reversed journal entries cannot be deleted';
                    END IF;

                    RETURN OLD;
                END IF;

                IF OLD.status <> 'draft' THEN
                    RAISE EXCEPTION
                        'Posted or reversed journal entries cannot be modified';
                END IF;

                IF NEW.status NOT IN ('draft', 'posted') THEN
                    RAISE EXCEPTION
                        'Invalid journal status transition';
                END IF;

                IF NEW.status = 'posted' THEN
                    IF NEW.posted_at IS NULL
                       OR NEW.posted_by IS NULL THEN
                        RAISE EXCEPTION
                            'Posting requires a user and timestamp';
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER trg_protect_journal_entry
            BEFORE UPDATE OR DELETE ON journal_entries
            FOR EACH ROW
            EXECUTE FUNCTION protect_journal_entry();

            CREATE FUNCTION protect_journal_entry_line()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            DECLARE
                parent_status varchar(20);
            BEGIN
                IF TG_OP = 'UPDATE'
                   AND NEW.journal_entry_id <> OLD.journal_entry_id THEN
                    RAISE EXCEPTION
                        'Journal lines cannot be moved between journals';
                END IF;

                    IF TG_OP = 'DELETE' THEN
                    SELECT status
                    INTO parent_status
                    FROM journal_entries
                    WHERE id = OLD.journal_entry_id
                    FOR UPDATE;
                ELSE
                    SELECT status
                    INTO parent_status
                    FROM journal_entries
                    WHERE id = NEW.journal_entry_id
                    FOR UPDATE;
                END IF;
                IF parent_status IS DISTINCT FROM 'draft' THEN
                    RAISE EXCEPTION
                        'Only draft journal lines can be modified';
                END IF;

                IF TG_OP = 'DELETE' THEN
                    RETURN OLD;
                END IF;

                RETURN NEW;
            END;
            $$;

            CREATE TRIGGER trg_protect_journal_entry_line
            BEFORE INSERT OR UPDATE OR DELETE ON journal_entry_lines
            FOR EACH ROW
            EXECUTE FUNCTION protect_journal_entry_line();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS trg_protect_journal_entry_line
                ON journal_entry_lines;

            DROP FUNCTION IF EXISTS protect_journal_entry_line();

            DROP TRIGGER IF EXISTS trg_protect_journal_entry
                ON journal_entries;

            DROP FUNCTION IF EXISTS protect_journal_entry();
        SQL);
    }
};