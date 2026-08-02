<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use Illuminate\Support\Str;

class LocalRulesAiProvider implements AiProvider
{
    public function generateText(string $task, array $context): array
    {
        return match ($task) {
            'business_description' => $this->businessDescription($context),
            'review_summary' => $this->reviewSummary($context),
            default => [
                'text' => '',
                'confidence' => 0.50,
                'provider' => 'local',
                'model' => 'local-rules-v1',
            ],
        };
    }

    public function score(string $task, array $context): array
    {
        return match ($task) {
            'lead_score' => $this->leadScore($context),
            'fraud_risk' => $this->fraudRisk($context),
            default => [
                'score' => 0,
                'confidence' => 0.50,
                'factors' => [],
                'provider' => 'local',
                'model' => 'local-rules-v1',
            ],
        };
    }

    private function businessDescription(array $context): array
    {
        $name = $context['trading_name'] ?? 'This business';
        $city = $context['city'] ?? 'Somalia';
        $services = collect($context['services'] ?? [])
            ->filter()
            ->take(4)
            ->implode(', ');

        $text = $services !== ''
            ? "{$name} provides {$services} in {$city}. The business profile helps customers review services, contact details, location, and verification information."
            : "{$name} serves customers in {$city}. The business profile helps customers review services, contact details, location, and verification information.";

        return [
            'text' => $text,
            'confidence' => 0.72,
            'provider' => 'local',
            'model' => 'local-rules-v1',
        ];
    }

    private function reviewSummary(array $context): array
    {
        $reviews = collect($context['reviews'] ?? []);
        $count = $reviews->count();

        if ($count === 0) {
            $text = 'No published reviews are available yet.';
        } else {
            $average = round((float) $reviews->avg('rating'), 1);
            $positive = $reviews->where('rating', '>=', 4)->count();
            $ratio = round(($positive / $count) * 100);

            $text = "Based on {$count} published reviews, the average rating is {$average} out of 5. {$ratio}% of reviewers gave a rating of 4 or 5.";
        }

        return [
            'text' => $text,
            'confidence' => $count >= 5 ? 0.85 : 0.65,
            'provider' => 'local',
            'model' => 'local-rules-v1',
        ];
    }

    private function leadScore(array $context): array
    {
        $score = 20;
        $factors = [];

        foreach ([
            'budget' => ! empty($context['budget_min']) || ! empty($context['budget_max']),
            'deadline' => ! empty($context['required_by']),
            'category' => ! empty($context['category_id']),
            'city' => ! empty($context['city_id']),
            'contact' => ! empty($context['contact_email']) || ! empty($context['contact_phone']),
        ] as $name => $present) {
            if ($present) {
                $increment = match ($name) {
                    'budget' => 20,
                    'deadline' => 15,
                    'category' => 15,
                    'city' => 10,
                    'contact' => 20,
                };

                $score += $increment;
                $factors[$name] = $increment;
            }
        }

        $score = min($score, 100);

        return [
            'score' => $score,
            'grade' => match (true) {
                $score >= 85 => 'A',
                $score >= 70 => 'B',
                $score >= 50 => 'C',
                default => 'D',
            },
            'confidence' => 0.82,
            'factors' => $factors,
            'provider' => 'local',
            'model' => 'local-rules-v1',
        ];
    }

    private function fraudRisk(array $context): array
    {
        $score = 0;
        $signals = [];
        $text = Str::lower((string) ($context['text'] ?? ''));

        foreach ([
            'external_link' => ['http://', 'https://'],
            'urgent_payment' => ['send money now', 'urgent transfer'],
            'off_platform' => ['whatsapp me only', 'telegram only'],
            'guarantee_claim' => ['guaranteed profit', '100% guaranteed'],
        ] as $code => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($text, $pattern)) {
                    $score += 20;
                    $signals[] = $code;
                    break;
                }
            }
        }

        return [
            'score' => min($score, 100),
            'confidence' => 0.70,
            'factors' => $signals,
            'provider' => 'local',
            'model' => 'local-rules-v1',
        ];
    }
}
