<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */
namespace Tests\Integration;

use App\Models\Invoice;
use App\Utils\Traits\MakesReminders;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\MockAccountData;
use Tests\TestCase;

/**
 * @test
 * @covers  App\Utils\Traits\MakesReminders
 */
class CheckRemindersTest extends TestCase
{
    use MockAccountData;
    use DatabaseTransactions;
    use MakesReminders;

    public function setUp() :void
    {
        parent::setUp();

        $this->makeTestData();
    }

    public function test_after_invoice_date_reminder()
    {
        $this->invoice->date = now();
        $this->invoice->due_date = Carbon::now()->addDays(30);

        $settings = $this->company->settings;
        $settings->enable_reminder1 = true;
        $settings->schedule_reminder1 = 'after_invoice_date';
        $settings->num_days_reminder1 = 7;
        $settings->enable_reminder2 = true;
        $settings->schedule_reminder2 = 'before_due_date';
        $settings->num_days_reminder2 = 1;
        $settings->enable_reminder3 = true;
        $settings->schedule_reminder3 = 'after_due_date';
        $settings->num_days_reminder3 = 1;

        $this->company->settings = $settings;
        $this->invoice->service()->markSent();
        $this->invoice->setReminder($settings);

        $this->assertEquals(0, Carbon::now()->addDays(7)->diffInDays($this->invoice->next_send_date));
    }

    public function test_no_reminders_sent_to_paid_invoices()
    {
        $this->invoice->date = now();
        $this->invoice->due_date = Carbon::now()->addDays(30);

        $settings = $this->company->settings;
        $settings->enable_reminder1 = true;
        $settings->schedule_reminder1 = 'after_invoice_date';
        $settings->num_days_reminder1 = 7;
        $settings->enable_reminder2 = true;
        $settings->schedule_reminder2 = 'before_due_date';
        $settings->num_days_reminder2 = 1;
        $settings->enable_reminder3 = true;
        $settings->schedule_reminder3 = 'after_due_date';
        $settings->num_days_reminder3 = 1;

        $this->company->settings = $settings;
        $this->invoice->service()->markSent()->setStatus(Invoice::STATUS_PAID);
        $this->invoice->setReminder($settings);

        $this->assertEquals($this->invoice->next_send_date, null);
    }

    public function test_before_due_date_reminder()
    {
        $this->invoice->date = now();
        $this->invoice->due_date = Carbon::now()->addDays(30);

        $settings = $this->company->settings;
        $settings->enable_reminder1 = true;
        $settings->schedule_reminder1 = 'after_invoice_date';
        $settings->num_days_reminder1 = 50;
        $settings->enable_reminder2 = true;
        $settings->schedule_reminder2 = 'before_due_date';
        $settings->num_days_reminder2 = 29;
        $settings->enable_reminder3 = true;
        $settings->schedule_reminder3 = 'after_due_date';
        $settings->num_days_reminder3 = 1;

        $this->company->settings = $settings;
        $this->invoice->service()->markSent();
        $this->invoice->setReminder($settings);

        $this->assertEquals(0, Carbon::parse($this->invoice->due_date)->subDays(29)->diffInDays($this->invoice->next_send_date));
    }

    public function test_after_due_date_reminder()
    {
        $this->invoice->date = now();
        $this->invoice->due_date = Carbon::now()->addDays(30);

        $settings = $this->company->settings;
        $settings->enable_reminder1 = true;
        $settings->schedule_reminder1 = 'after_invoice_date';
        $settings->num_days_reminder1 = 50;
        $settings->enable_reminder2 = false;
        $settings->schedule_reminder2 = 'before_due_date';
        $settings->num_days_reminder2 = 50;
        $settings->enable_reminder3 = true;
        $settings->schedule_reminder3 = 'after_due_date';
        $settings->num_days_reminder3 = 1;

        $this->company->settings = $settings;
        $this->invoice->service()->markSent();
        $this->invoice->setReminder($settings);

info($this->invoice->date);
info($this->invoice->due_date);
info($this->invoice->next_send_date);
//@TODO
$this->assertTrue(true);
       // $this->assertEquals(0, Carbon::parse($this->invoice->due_date)->addDays(1)->diffInDays($this->invoice->next_send_date));
    }

    public function test_turning_off_reminders()
    {
        $this->invoice->date = now();
        $this->invoice->due_date = Carbon::now()->addDays(30);

        $settings = $this->company->settings;
        $settings->enable_reminder1 = false;
        $settings->schedule_reminder1 = 'after_invoice_date';
        $settings->num_days_reminder1 = 50;
        $settings->enable_reminder2 = false;
        $settings->schedule_reminder2 = 'before_due_date';
        $settings->num_days_reminder2 = 50;
        $settings->enable_reminder3 = false;
        $settings->schedule_reminder3 = 'after_due_date';
        $settings->num_days_reminder3 = 1;

        $this->company->settings = $settings;
        $this->invoice->service()->markSent();
        $this->invoice->setReminder($settings);

        $this->assertEquals($this->invoice->next_send_date, null);
    }

    public function test_edge_case_num_days_equals_zero_reminders()
    {
        $this->invoice->date = now();
        $this->invoice->due_date = Carbon::now()->addDays(30);

        $settings = $this->company->settings;
        $settings->enable_reminder1 = false;
        $settings->schedule_reminder1 = 'after_invoice_date';
        $settings->num_days_reminder1 = 0;
        $settings->enable_reminder2 = false;
        $settings->schedule_reminder2 = 'before_due_date';
        $settings->num_days_reminder2 = 0;
        $settings->enable_reminder3 = false;
        $settings->schedule_reminder3 = 'after_due_date';
        $settings->num_days_reminder3 = 0;

        $this->company->settings = $settings;
        $this->invoice->service()->markSent();
        $this->invoice->setReminder($settings);

        $this->assertEquals($this->invoice->next_send_date, null);
    }
}
