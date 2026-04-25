<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $announcements = [
            ['title' => 'Q2 Company Goals Update', 'type' => 'general', 'is_pinned' => true, 'requires_acknowledgement' => false],
            ['title' => 'Updated Remote Work Policy', 'type' => 'policy', 'is_pinned' => false, 'requires_acknowledgement' => true],
            ['title' => 'Annual Company Picnic - Save the Date!', 'type' => 'event', 'is_pinned' => false, 'requires_acknowledgement' => false],
            ['title' => 'Security Incident Response Protocol', 'type' => 'urgent', 'is_pinned' => true, 'requires_acknowledgement' => true],
            ['title' => 'New Health Benefits Enrollment Open', 'type' => 'general', 'is_pinned' => false, 'requires_acknowledgement' => true],
        ];

        foreach (Company::all() as $company) {
            $adminUser = User::where("company_id", $company->id)->whereIn("role", ["company_admin", "hr_manager"])->first(); if (!$adminUser) continue;
            $companyUsers = User::where('company_id', $company->id)->get();

            foreach ($announcements as $i => $data) {
                $publishedAt = Carbon::now()->subDays(rand(1, 20));

                $announcement = Announcement::create([
                    'company_id' => $company->id,
                    'title' => $data['title'],
                    'body' => fake()->paragraphs(3, true),
                    'type' => $data['type'],
                    'is_pinned' => $data['is_pinned'],
                    'requires_acknowledgement' => $data['requires_acknowledgement'],
                    'published_at' => $publishedAt,
                    'expires_at' => $data['type'] === 'event' ? Carbon::now()->addDays(30) : null,
                    'created_by' => $adminUser->id,
                ]);

                // Some users read/acknowledge
                $readCount = $i < 3 ? $companyUsers->count() : rand(2, (int)($companyUsers->count() * 0.6));
                $readers = $companyUsers->random(min($readCount, $companyUsers->count()));

                foreach ($readers as $user) {
                    AnnouncementRead::create([
                        'announcement_id' => $announcement->id,
                        'user_id' => $user->id,
                        'read_at' => $publishedAt->copy()->addHours(rand(1, 48)),
                        'acknowledged_at' => $data['requires_acknowledgement'] && rand(0, 1)
                            ? $publishedAt->copy()->addHours(rand(2, 72))
                            : null,
                    ]);
                }
            }
        }
    }
}
