@extends('layouts.admin')

@section('title')
    Node Backup Statistics
@endsection

@section('content-header')
    <h1>Node Backup Statistics<small>This part allows you to see the statistics of the Node Backup</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.node-backup') }}">Node Backup</a></li>
        <li class="active">Statistics</li>
    </ol>
@endsection

@section('content')

@php
function format_bytes(float $bytes, $precision = 2) { 
    $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB'); 
   
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
   
    $bytes /= (1 << (10 * $pow)); 
   
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
@endphp
    <div class="row" style="display: flex; justify-content: center;">
        <div class="col-xs-12 col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-server"></i></span>
                <div class="info-box-content number-info-box-content">
                    <span class="info-box-text">Total of Node Backup Groups</span>
                    <span class="info-box-number">{{ $backup_groups->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-server"></i></span>
                <div class="info-box-content number-info-box-content">
                    <span class="info-box-text">Total of Node Backups</span>
                    <span class="info-box-number">{{ $node_backups->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-server"></i></span>
                <div class="info-box-content number-info-box-content">
                    <span class="info-box-text">Total of Node Backup Servers</span>
                    <span class="info-box-number">{{ $node_backup_servers->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-3">
            <div class="info-box bg-blue">
                <span class="info-box-icon"><i class="fa fa-server"></i></span>
                <div class="info-box-content number-info-box-content">
                    <span class="info-box-text">Total Size</span>
                    <span class="info-box-number">{{ format_bytes($node_backup_servers->sum('bytes')) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        @if ($node_backups->count() > 1)
            <div class="col-xs-12 col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Evolution of the total size occupied by all the backups in function of the time (MiB)</h3>
                    </div>
                    <div class="box-body">
                        <div id="sizeTimeChart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Evolution of the number of backups in function of the time</h3>
                    </div>
                    <div class="box-body">
                        <div id="numberTimeChart"></div>
                    </div>
                </div>
            </div>
        @else
            <div class="col-xs-12 col-md-12" style="display: flex; justify-content: center;">
                <div class="alert alert-info">
                    <h4><i class="icon fa fa-info"></i> Information</h4>
                    There is not enough data to display the statistics.
                </div>
            </div>
        @endif
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        @if ($node_backups->count() > 1)
            const sizeTimeChartOptions = {
                series: [{
                    name: 'Size on that day (MiB)',
                    data: [
                        @foreach ($cumulativeBytes->sortBy('date') as $dailyBytes)
                            [{{ $dailyBytes['date']->timestamp * 1000 }}, {{ $dailyBytes['total_mebibytes'] }}],
                        @endforeach
                    ]
                }],
                chart: {
                    id: 'evolution-of-the-total-size-occupied-by-all-the-backups-in-function-of-the-time',
                    type: 'area',
                    height: 350,
                    background: 'transparent',
                    zoom: {
                        autoScaleYaxis: true
                    }
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 0,
                    colors: '#CAD1D8',
                    style: 'hollow',
                },
                xaxis: {
                    type: 'datetime',
                    min: {{ $cumulativeBytes->min('date')->timestamp * 1000 }},
                    labels: {
                        style: {
                            colors: '#CAD1D8',
                        },
                    },
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#CAD1D8',
                        },
                    },
                },
                tooltip: {
                    theme: 'dark',
                    x: {
                        format: 'dd MMM yyyy HH:mm',
                    }
                },
                grid: {
                    xaxis: {
                        lines: {
                            show: false
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    },
                    row: {
                        colors: ['transparent'],
                    },
                },
                theme: {
                    mode: 'light',
                },
            };

            new ApexCharts(document.querySelector("#sizeTimeChart"), sizeTimeChartOptions).render();

            const numberTimeChartOptions = {
                series: [{
                    name: 'Number of backups on that day',
                    data: [
                        @foreach ($dailyBackupCounts->sortBy('date') as $dailyCount)
                            [{{ $dailyCount['date']->timestamp * 1000 }}, {{ $dailyCount['count'] }}],
                        @endforeach
                    ]
                }],
                chart: {
                    id: 'evolution-of-the-number-of-backups-in-function-of-the-time',
                    type: 'area',
                    height: 350,
                    background: 'transparent',
                    zoom: {
                        autoScaleYaxis: true
                    }
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 0,
                    colors: '#CAD1D8',
                    style: 'hollow',
                },
                xaxis: {
                    type: 'datetime',
                    min: {{ $dailyBackupCounts->min('date')->timestamp * 1000 }},
                    labels: {
                        style: {
                            colors: '#CAD1D8',
                        },
                    },
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#CAD1D8',
                        },
                    },
                },
                tooltip: {
                    theme: 'dark',
                    x: {
                        format: 'dd MMM yyyy HH:mm',
                    },
                    y: {
                        formatter: function (value) {
                            return value;
                        }
                    }
                },
                grid: {
                    xaxis: {
                        lines: {
                            show: false
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    },
                    row: {
                        colors: ['transparent'],
                    },
                },
                theme: {
                    mode: 'light',
                },
            };

            new ApexCharts(document.querySelector("#numberTimeChart"), numberTimeChartOptions).render();
        @endif
    </script>
@endsection
