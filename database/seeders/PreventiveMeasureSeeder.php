<?php

namespace Database\Seeders;

use App\Models\PreventiveMeasure;
use Illuminate\Database\Seeder;

class PreventiveMeasureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $measures = [
            // Fire Safety
            [
                'title' => 'Fire Safety: Stop, Drop, and Roll',
                'description' => 'Learn the essential technique of Stop, Drop, and Roll if your clothes catch fire. This simple action can save your life by smothering flames and preventing severe burns.',
                'author' => 'Safety Team',
                'category' => 'fire',
                'video_path' => 'videos/How to Survive a Falling Elevator, According to Science.mp4',
                'thumbnail_url' => null,
                'sort_order' => 1,
            ],
            [
                'title' => 'How to Use a Fire Extinguisher (PASS Method)',
                'description' => 'Master the PASS technique: Pull the pin, Aim at the base of the fire, Squeeze the handle, and Sweep from side to side. Essential knowledge for every person.',
                'author' => 'Fire Department',
                'category' => 'fire',
                'video_path' => 'videos/What Are The LIMITS of HUMAN SURVIVAL  #SURVIVAL #MYTHS #DEBUNKED.mp4',
                'thumbnail_url' => null,
                'sort_order' => 2,
            ],
            [
                'title' => 'What to do When Trapped Under Debris',
                'description' => 'Learn the proper procedures for surviving and escaping when trapped under debris. Know how to signal for help and protect yourself.',
                'author' => 'Emergency Response Team',
                'category' => 'general',
                'video_path' => 'videos/What to do When Trapped Under Debris.mp4',
                'thumbnail_url' => null,
                'sort_order' => 3,
            ],

            // Earthquake Safety
            [
                'title' => 'Earthquake Safety: Drop, Cover, and Hold On',
                'description' => 'The Drop, Cover, and Hold On technique is your best protection during an earthquake. Learn how to protect yourself from falling objects and debris.',
                'author' => 'Disaster Preparedness Office',
                'category' => 'earthquake',
                'video_path' => 'videos/How to Survive a Falling Elevator, According to Science.mp4',
                'thumbnail_url' => null,
                'sort_order' => 4,
            ],
            [
                'title' => 'What to Do After an Earthquake',
                'description' => 'After the shaking stops, check for injuries, be prepared for aftershocks, and know how to safely exit a damaged building.',
                'author' => 'Emergency Management',
                'category' => 'earthquake',
                'video_path' => 'videos/What Are The LIMITS of HUMAN SURVIVAL  #SURVIVAL #MYTHS #DEBUNKED.mp4',
                'thumbnail_url' => null,
                'sort_order' => 5,
            ],

            // Medical Emergency
            [
                'title' => 'Basic First Aid: CPR for Adults',
                'description' => 'Learn hands-only CPR that can double or triple a cardiac arrest victim\'s chance of survival. This life-saving skill takes just minutes to learn.',
                'author' => 'Medical Team',
                'category' => 'medical',
                'video_path' => 'videos/What to do When Trapped Under Debris.mp4',
                'thumbnail_url' => null,
                'sort_order' => 6,
            ],
            [
                'title' => 'How to Help a Choking Person',
                'description' => 'The Heimlich maneuver can save a choking person\'s life. Learn the proper technique for helping both adults and children who are choking.',
                'author' => 'Health & Safety',
                'category' => 'medical',
                'video_path' => 'videos/How to Survive a Falling Elevator, According to Science.mp4',
                'thumbnail_url' => null,
                'sort_order' => 7,
            ],
            [
                'title' => 'Treating Burns: First Aid Tips',
                'description' => 'Know how to properly treat minor burns with cool running water, and when to seek professional medical help for more serious burns.',
                'author' => 'Medical Team',
                'category' => 'medical',
                'video_path' => 'videos/What Are The LIMITS of HUMAN SURVIVAL  #SURVIVAL #MYTHS #DEBUNKED.mp4',
                'thumbnail_url' => null,
                'sort_order' => 8,
            ],

            // Flood Safety
            [
                'title' => 'Flood Safety: What You Need to Know',
                'description' => 'Floods can happen quickly. Learn how to prepare, what to do during a flood, and how to stay safe. Never walk or drive through flood waters.',
                'author' => 'Disaster Response Team',
                'category' => 'flood',
                'video_path' => 'videos/What to do When Trapped Under Debris.mp4',
                'thumbnail_url' => null,
                'sort_order' => 9,
            ],

            // General Emergency
            [
                'title' => 'Emergency Kit Essentials',
                'description' => 'Be prepared for any emergency with a well-stocked emergency kit. Learn what essential items you should have ready at home, work, and in your car.',
                'author' => 'Preparedness Office',
                'category' => 'general',
                'video_path' => 'videos/How to Survive a Falling Elevator, According to Science.mp4',
                'thumbnail_url' => null,
                'sort_order' => 10,
            ],
        ];

        foreach ($measures as $measure) {
            PreventiveMeasure::create($measure);
        }
    }
}
