/**
 * Peanut Suite Charts
 *
 * Chart.js wrapper for consistent chart styling
 */

(function() {
    'use strict';

    window.PeanutCharts = {
        // Default colors
        colors: {
            primary: '#0073aa',
            secondary: '#3b82f6',
            success: '#10b981',
            warning: '#f59e0b',
            error: '#ef4444',
            gray: '#64748b',
            palette: [
                '#3b82f6', // blue
                '#10b981', // green
                '#f59e0b', // yellow
                '#ef4444', // red
                '#8b5cf6', // purple
                '#ec4899', // pink
                '#06b6d4', // cyan
                '#f97316', // orange
            ]
        },

        // Default options
        defaultOptions: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    borderColor: '#334155',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 6,
                    displayColors: true
                }
            }
        },

        /**
         * Create a line chart
         * Accepts either (canvasId, labels, datasets, options) or (canvasId, config)
         */
        line: function(canvasId, labelsOrConfig, datasets, options) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            // Handle object-style configuration
            var labels, chartDatasets, chartOptions;
            if (typeof labelsOrConfig === 'object' && !Array.isArray(labelsOrConfig)) {
                labels = labelsOrConfig.labels;
                chartDatasets = labelsOrConfig.datasets;
                chartOptions = datasets; // Third param becomes options
            } else {
                labels = labelsOrConfig;
                chartDatasets = datasets;
                chartOptions = options;
            }

            // Process datasets with default styling
            var processedDatasets = chartDatasets.map(function(dataset, index) {
                return Object.assign({
                    borderColor: PeanutCharts.colors.palette[index % PeanutCharts.colors.palette.length],
                    backgroundColor: PeanutCharts.hexToRgba(
                        PeanutCharts.colors.palette[index % PeanutCharts.colors.palette.length],
                        0.1
                    ),
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }, dataset);
            });

            var mergedOptions = this.mergeOptions({
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e2e8f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11
                            },
                            padding: 8
                        }
                    }
                }
            }, chartOptions);

            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: processedDatasets
                },
                options: mergedOptions
            });
        },

        /**
         * Create a bar chart
         * Accepts either (canvasId, labels, datasets, options) or (canvasId, config)
         */
        bar: function(canvasId, labelsOrConfig, datasets, options) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            // Handle object-style configuration
            var labels, chartDatasets, chartOptions;
            if (typeof labelsOrConfig === 'object' && !Array.isArray(labelsOrConfig)) {
                labels = labelsOrConfig.labels;
                chartDatasets = labelsOrConfig.datasets;
                chartOptions = datasets;
            } else {
                labels = labelsOrConfig;
                chartDatasets = datasets;
                chartOptions = options;
            }

            // Process datasets with default styling
            var processedDatasets = chartDatasets.map(function(dataset, index) {
                return Object.assign({
                    backgroundColor: PeanutCharts.colors.palette[index % PeanutCharts.colors.palette.length],
                    borderRadius: 4,
                    maxBarThickness: 40
                }, dataset);
            });

            var mergedOptions = this.mergeOptions({
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e2e8f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11
                            },
                            padding: 8
                        }
                    }
                }
            }, chartOptions);

            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: processedDatasets
                },
                options: mergedOptions
            });
        },

        /**
         * Create a doughnut chart
         * Accepts either (canvasId, labels, data, options) or (canvasId, config)
         */
        doughnut: function(canvasId, labelsOrConfig, data, options) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            // Handle object-style configuration
            var labels, chartData, chartOptions;
            if (typeof labelsOrConfig === 'object' && !Array.isArray(labelsOrConfig) && labelsOrConfig.labels) {
                labels = labelsOrConfig.labels;
                chartData = labelsOrConfig.datasets ? labelsOrConfig.datasets[0].data : labelsOrConfig.data;
                var datasetColors = labelsOrConfig.datasets ? labelsOrConfig.datasets[0].backgroundColor : null;
                chartOptions = data;
            } else {
                labels = labelsOrConfig;
                chartData = data;
                chartOptions = options;
            }

            var mergedOptions = this.mergeOptions({
                cutout: '65%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            color: '#64748b',
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }, chartOptions);

            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: chartData,
                        backgroundColor: datasetColors || this.colors.palette.slice(0, chartData.length),
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: mergedOptions
            });
        },

        /**
         * Create a horizontal bar chart
         * Accepts either (canvasId, labels, datasets, options) or (canvasId, config)
         */
        horizontalBar: function(canvasId, labelsOrConfig, datasets, options) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            // Handle object-style configuration
            var labels, chartDatasets, chartOptions;
            if (typeof labelsOrConfig === 'object' && !Array.isArray(labelsOrConfig)) {
                labels = labelsOrConfig.labels;
                chartDatasets = labelsOrConfig.datasets;
                chartOptions = datasets;
            } else {
                labels = labelsOrConfig;
                chartDatasets = datasets;
                chartOptions = options;
            }

            var processedDatasets = chartDatasets.map(function(dataset, index) {
                return Object.assign({
                    backgroundColor: PeanutCharts.colors.palette[index % PeanutCharts.colors.palette.length],
                    borderRadius: 4,
                    maxBarThickness: 24
                }, dataset);
            });

            var mergedOptions = this.mergeOptions({
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#e2e8f0',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64748b',
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }, chartOptions);

            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: processedDatasets
                },
                options: mergedOptions
            });
        },

        /**
         * Create a sparkline (small inline chart)
         */
        sparkline: function(canvasId, data, color) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            color = color || this.colors.primary;

            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(function(_, i) { return i; }),
                    datasets: [{
                        data: data,
                        borderColor: color,
                        backgroundColor: this.hexToRgba(color, 0.1),
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    }
                }
            });
        },

        /**
         * Merge options with defaults
         */
        mergeOptions: function(baseOptions, customOptions) {
            return this.deepMerge(
                this.deepMerge({}, this.defaultOptions),
                this.deepMerge(baseOptions || {}, customOptions || {})
            );
        },

        /**
         * Deep merge objects
         */
        deepMerge: function(target, source) {
            var output = Object.assign({}, target);
            if (this.isObject(target) && this.isObject(source)) {
                Object.keys(source).forEach(function(key) {
                    if (PeanutCharts.isObject(source[key])) {
                        if (!(key in target)) {
                            Object.assign(output, { [key]: source[key] });
                        } else {
                            output[key] = PeanutCharts.deepMerge(target[key], source[key]);
                        }
                    } else {
                        Object.assign(output, { [key]: source[key] });
                    }
                });
            }
            return output;
        },

        /**
         * Check if value is object
         */
        isObject: function(item) {
            return (item && typeof item === 'object' && !Array.isArray(item));
        },

        /**
         * Convert hex to rgba
         */
        hexToRgba: function(hex, alpha) {
            var r = parseInt(hex.slice(1, 3), 16);
            var g = parseInt(hex.slice(3, 5), 16);
            var b = parseInt(hex.slice(5, 7), 16);
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
        },

        /**
         * Format large numbers (1000 -> 1K)
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        /**
         * Generate date labels for time series
         */
        generateDateLabels: function(days) {
            var labels = [];
            var today = new Date();
            for (var i = days - 1; i >= 0; i--) {
                var date = new Date(today);
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            }
            return labels;
        }
    };

})();
