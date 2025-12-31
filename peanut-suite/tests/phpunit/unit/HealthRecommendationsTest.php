<?php
/**
 * Unit tests for Health_Recommendations class
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once PEANUT_PLUGIN_DIR . 'modules/health-reports/class-health-recommendations.php';

class HealthRecommendationsTest extends Peanut_Test_Case {

    private Health_Recommendations $recommendations;

    protected function setUp(): void {
        parent::setUp();
        $this->recommendations = new Health_Recommendations();
    }

    public function test_generate_returns_array(): void {
        $result = $this->recommendations->generate([], []);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_site_with_ssl_issue_generates_critical_recommendation(): void {
        $sites_data = [
            'items' => [
                [
                    'name' => 'example.com',
                    'score' => 75,
                    'issues' => ['SSL certificate expiring'],
                ],
            ],
        ];

        $result = $this->recommendations->generate($sites_data, []);

        $this->assertNotEmpty($result);
        $ssl_rec = array_filter($result, fn($r) => $r['category'] === 'ssl');
        $this->assertNotEmpty($ssl_rec);

        $first_ssl = array_values($ssl_rec)[0];
        $this->assertEquals('critical', $first_ssl['priority']);
        $this->assertEquals('example.com', $first_ssl['site']);
    }

    public function test_site_with_wordpress_update_generates_high_recommendation(): void {
        $sites_data = [
            'items' => [
                [
                    'name' => 'mysite.com',
                    'score' => 80,
                    'issues' => ['WordPress update available'],
                ],
            ],
        ];

        $result = $this->recommendations->generate($sites_data, []);

        $wp_rec = array_filter($result, fn($r) => $r['category'] === 'wordpress');
        $this->assertNotEmpty($wp_rec);

        $first_wp = array_values($wp_rec)[0];
        $this->assertEquals('high', $first_wp['priority']);
    }

    public function test_site_with_many_plugin_updates_generates_high_priority(): void {
        $sites_data = [
            'items' => [
                [
                    'name' => 'blog.com',
                    'score' => 70,
                    'issues' => ['8 plugins need update'],
                ],
            ],
        ];

        $result = $this->recommendations->generate($sites_data, []);

        $plugin_rec = array_filter($result, fn($r) => $r['category'] === 'plugins');
        $this->assertNotEmpty($plugin_rec);

        $first = array_values($plugin_rec)[0];
        $this->assertEquals('high', $first['priority']);
        $this->assertStringContainsString('8 plugins', $first['message']);
    }

    public function test_site_with_few_plugin_updates_generates_medium_priority(): void {
        $sites_data = [
            'items' => [
                [
                    'name' => 'blog.com',
                    'score' => 85,
                    'issues' => ['2 plugins need update'],
                ],
            ],
        ];

        $result = $this->recommendations->generate($sites_data, []);

        $plugin_rec = array_filter($result, fn($r) => $r['category'] === 'plugins');
        $first = array_values($plugin_rec)[0];
        $this->assertEquals('medium', $first['priority']);
    }

    public function test_site_with_low_score_generates_high_recommendation(): void {
        $sites_data = [
            'items' => [
                [
                    'name' => 'unhealthy-site.com',
                    'score' => 45,
                    'issues' => [],
                ],
            ],
        ];

        $result = $this->recommendations->generate($sites_data, []);

        $site_rec = array_filter($result, fn($r) => $r['category'] === 'site');
        $this->assertNotEmpty($site_rec);

        $first = array_values($site_rec)[0];
        $this->assertEquals('high', $first['priority']);
        $this->assertStringContainsString('45/100', $first['message']);
    }

    public function test_server_with_high_cpu_generates_critical_recommendation(): void {
        $servers_data = [
            'items' => [
                [
                    'name' => 'Server 1',
                    'score' => 70,
                    'issues' => ['CPU at 95%'],
                ],
            ],
        ];

        $result = $this->recommendations->generate([], $servers_data);

        $cpu_rec = array_filter($result, fn($r) => $r['category'] === 'cpu');
        $this->assertNotEmpty($cpu_rec);

        $first = array_values($cpu_rec)[0];
        $this->assertEquals('critical', $first['priority']);
        $this->assertStringContainsString('95%', $first['message']);
    }

    public function test_server_with_moderate_disk_generates_high_recommendation(): void {
        $servers_data = [
            'items' => [
                [
                    'name' => 'Server 2',
                    'score' => 80,
                    'issues' => ['Disk at 85%'],
                ],
            ],
        ];

        $result = $this->recommendations->generate([], $servers_data);

        $disk_rec = array_filter($result, fn($r) => $r['category'] === 'disk');
        $this->assertNotEmpty($disk_rec);

        $first = array_values($disk_rec)[0];
        $this->assertEquals('high', $first['priority']);
    }

    public function test_server_with_stopped_services_generates_critical(): void {
        $servers_data = [
            'items' => [
                [
                    'name' => 'Server 3',
                    'score' => 60,
                    'issues' => ['2 services stopped'],
                ],
            ],
        ];

        $result = $this->recommendations->generate([], $servers_data);

        $service_rec = array_filter($result, fn($r) => $r['category'] === 'services');
        $this->assertNotEmpty($service_rec);

        $first = array_values($service_rec)[0];
        $this->assertEquals('critical', $first['priority']);
        $this->assertStringContainsString('2 critical services', $first['message']);
    }

    public function test_recommendations_sorted_by_priority(): void {
        $sites_data = [
            'items' => [
                [
                    'name' => 'site1.com',
                    'score' => 80,
                    'issues' => ['2 plugins need update'], // medium
                ],
                [
                    'name' => 'site2.com',
                    'score' => 70,
                    'issues' => ['SSL certificate expiring'], // critical
                ],
                [
                    'name' => 'site3.com',
                    'score' => 75,
                    'issues' => ['WordPress update available'], // high
                ],
            ],
        ];

        $result = $this->recommendations->generate($sites_data, []);

        // First recommendation should be critical
        $this->assertEquals('critical', $result[0]['priority']);

        // Verify ordering
        $priorities = array_map(fn($r) => $r['priority'], $result);
        $priority_order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];

        for ($i = 1; $i < count($priorities); $i++) {
            $this->assertGreaterThanOrEqual(
                $priority_order[$priorities[$i - 1]],
                $priority_order[$priorities[$i]],
                'Recommendations should be sorted by priority'
            );
        }
    }

    public function test_filter_by_category(): void {
        $recommendations = [
            ['category' => 'ssl', 'priority' => 'critical', 'message' => 'SSL issue'],
            ['category' => 'plugins', 'priority' => 'medium', 'message' => 'Plugin update'],
            ['category' => 'ssl', 'priority' => 'high', 'message' => 'Another SSL issue'],
        ];

        $result = $this->recommendations->filter_by_category($recommendations, 'ssl');

        $this->assertCount(2, $result);
        foreach ($result as $rec) {
            $this->assertEquals('ssl', $rec['category']);
        }
    }

    public function test_filter_by_priority(): void {
        $recommendations = [
            ['category' => 'ssl', 'priority' => 'critical', 'message' => 'SSL issue'],
            ['category' => 'plugins', 'priority' => 'medium', 'message' => 'Plugin update'],
            ['category' => 'cpu', 'priority' => 'critical', 'message' => 'CPU issue'],
        ];

        $result = $this->recommendations->filter_by_priority($recommendations, 'critical');

        $this->assertCount(2, $result);
        foreach ($result as $rec) {
            $this->assertEquals('critical', $rec['priority']);
        }
    }

    public function test_count_by_priority(): void {
        $recommendations = [
            ['priority' => 'critical'],
            ['priority' => 'critical'],
            ['priority' => 'high'],
            ['priority' => 'medium'],
            ['priority' => 'medium'],
            ['priority' => 'medium'],
        ];

        $result = $this->recommendations->count_by_priority($recommendations);

        $this->assertEquals(2, $result['critical']);
        $this->assertEquals(1, $result['high']);
        $this->assertEquals(3, $result['medium']);
        $this->assertEquals(0, $result['low']);
    }

    public function test_uptime_issues_generate_critical_recommendation(): void {
        $sites_data = [
            'items' => [
                [
                    'name' => 'down-site.com',
                    'score' => 50,
                    'issues' => ['Uptime below 99%'],
                ],
            ],
        ];

        $result = $this->recommendations->generate($sites_data, []);

        $uptime_rec = array_filter($result, fn($r) => $r['category'] === 'uptime');
        $this->assertNotEmpty($uptime_rec);

        $first = array_values($uptime_rec)[0];
        $this->assertEquals('critical', $first['priority']);
    }

    public function test_combined_sites_and_servers_data(): void {
        $sites_data = [
            'items' => [
                [
                    'name' => 'mysite.com',
                    'score' => 75,
                    'issues' => ['3 plugins need update'],
                ],
            ],
        ];

        $servers_data = [
            'items' => [
                [
                    'name' => 'Server 1',
                    'score' => 80,
                    'issues' => ['Disk at 88%'],
                ],
            ],
        ];

        $result = $this->recommendations->generate($sites_data, $servers_data);

        $categories = array_unique(array_column($result, 'category'));
        $this->assertContains('plugins', $categories);
        $this->assertContains('disk', $categories);
    }
}
