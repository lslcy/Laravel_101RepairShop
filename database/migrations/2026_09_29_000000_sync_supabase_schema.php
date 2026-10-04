<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration to sync Laravel's schema with the existing Supabase PostgreSQL database.
 *
 * Existing Supabase tables: customers (uuid PK), appliances, service_reports, transactions, appointments
 * Missing tables needed by Laravel: users, sessions, password_reset_tokens, cache, jobs,
 *   parts, service_details, service_prices, service_progress_comments, staff_comments,
 *   notifications, part_service_report
 */
return new class extends Migration {
    public function up(): void
    {
        // =====================================================================
        // 1. CREATE MISSING TABLES (Laravel-only tables that don't exist in Supabase)
        // =====================================================================

        // --- Users (admin/staff) ---
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('username')->unique();
                $table->string('email')->unique()->nullable();
                $table->string('profile_picture')->nullable();
                $table->string('profile_picture_public_id')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('bio')->nullable();
                $table->string('avatar')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('Technician');
                $table->string('role_title')->nullable();
                $table->string('status')->default('Active');
                $table->timestamp('last_login')->nullable();
                $table->rememberToken();
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
            });
        }

        // --- Password Reset Tokens ---
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // --- Sessions ---
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity')->index();
            });
        }

        // --- Cache ---
        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        // --- Jobs ---
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // --- Parts ---
        if (!Schema::hasTable('parts')) {
            Schema::create('parts', function (Blueprint $table) {
                $table->id();
                $table->string('part_no')->nullable();
                $table->text('name')->nullable(); // was 'description', renamed in later migration
                $table->decimal('price', 10, 2)->nullable();
                $table->integer('quantity_stock')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->string('deletion_reason')->nullable();
            });
        }

        // --- Service Details ---
        if (!Schema::hasTable('service_details')) {
            Schema::create('service_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('service_reports')->onDelete('cascade');
                $table->json('service_types')->nullable();
                $table->decimal('service_charge', 10, 2)->default(0);
                $table->date('date_repaired')->nullable();
                $table->date('date_delivered')->nullable();
                $table->text('complaint')->nullable();
                $table->decimal('labor', 10, 2)->default(0);
                $table->decimal('pullout_delivery', 10, 2)->default(0);
                $table->decimal('parts_total_charge', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->decimal('miscellaneous_cost', 10, 2)->default(0);
                $table->string('receptionist')->nullable();
                $table->string('manager')->nullable();
                $table->string('technician')->nullable();
                $table->string('released_by')->nullable();
                $table->timestamps();
            });
        }

        // --- Service Prices ---
        if (!Schema::hasTable('service_prices')) {
            Schema::create('service_prices', function (Blueprint $table) {
                $table->id();
                $table->string('service_name');
                $table->decimal('service_price', 10, 2);
                $table->timestamps();
            });
        }

        // --- Service Progress Comments ---
        if (!Schema::hasTable('service_progress_comments')) {
            Schema::create('service_progress_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_id')->constrained('service_reports')->onDelete('cascade');
                $table->string('progress_key');
                $table->longText('comment_text');
                $table->foreignId('created_by')->nullable()->constrained('users');
                $table->string('created_by_name')->nullable();
                $table->timestamps();
            });
        }

        // --- Staff Comments ---
        if (!Schema::hasTable('staff_comments')) {
            Schema::create('staff_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('users')->onDelete('cascade');
                $table->longText('comment_text');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        // --- Notifications ---
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // --- Part-ServiceReport pivot table ---
        if (!Schema::hasTable('part_service_report')) {
            Schema::create('part_service_report', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_report_id')->constrained()->onDelete('cascade');
                $table->foreignId('part_id')->constrained()->onDelete('cascade');
                $table->integer('quantity');
                $table->decimal('price', 10, 2)->comment('Price at the time of repair');
                $table->boolean('is_not_working')->default(false);
                $table->timestamps();
            });
        }

        // =====================================================================
        // 2. ADD MISSING COLUMNS TO EXISTING SUPABASE TABLES
        // =====================================================================

        // --- Customers: add soft-delete columns ---
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('customers', 'deleted_by')) {
                $table->uuid('deleted_by')->nullable();
            }
            if (!Schema::hasColumn('customers', 'deletion_reason')) {
                $table->string('deletion_reason')->nullable();
            }
        });

        // --- Appliances: add soft-delete columns ---
        Schema::table('appliances', function (Blueprint $table) {
            if (!Schema::hasColumn('appliances', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // --- Service Reports: add soft-delete and missing columns ---
        Schema::table('service_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('service_reports', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('service_reports', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
            }
            if (!Schema::hasColumn('service_reports', 'deletion_reason')) {
                $table->string('deletion_reason')->nullable();
            }
        });

        // --- Transactions: add soft-delete and missing columns ---
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('transactions', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'deletion_reason')) {
                $table->string('deletion_reason')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'paymongo_link_id')) {
                $table->string('paymongo_link_id')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'payment_url')) {
                $table->string('payment_url')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'reference_no')) {
                $table->string('reference_no')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'partial_payment_amount')) {
                $table->decimal('partial_payment_amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('transactions', 'payment_date')) {
                $table->date('payment_date')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'payment_due')) {
                $table->date('payment_due')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'received_by')) {
                $table->string('received_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Drop Laravel-only tables (won't affect Supabase-owned tables)
        Schema::dropIfExists('part_service_report');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('staff_comments');
        Schema::dropIfExists('service_progress_comments');
        Schema::dropIfExists('service_prices');
        Schema::dropIfExists('service_details');
        Schema::dropIfExists('parts');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        // Remove added columns from existing Supabase tables
        if (Schema::hasColumn('customers', 'deleted_at')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropColumn(['deleted_by', 'deletion_reason']);
            });
        }

        if (Schema::hasColumn('appliances', 'deleted_at')) {
            Schema::table('appliances', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('service_reports', 'deleted_at')) {
            Schema::table('service_reports', function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropColumn(['deleted_by', 'deletion_reason']);
            });
        }

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropColumn(['deleted_by', 'deletion_reason', 'paymongo_link_id', 'payment_url', 'reference_no', 'partial_payment_amount', 'payment_date', 'payment_due', 'received_by']);
            });
        }
    }
};
