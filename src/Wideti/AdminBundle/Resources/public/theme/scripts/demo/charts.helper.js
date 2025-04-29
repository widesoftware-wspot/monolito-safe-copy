var charts =
{
    // init charts on dashboard
    initGuestsCharts: function(data)
    {
        this.chart_ordered_bars.init(data);
    },

    initNetworkCharts: function(data)
    {
        this.chart_donut.init(data);
    },

    initPlatformChart: function(data)
    {
        this.chart_donut.init_platform_chart(data);
    },

    initDevicesChart: function(data)
    {
        this.chart_donut.init_devices_chart(data);
    },

    initRegisterModeChart: function(data)
    {
        this.chart_donut.init_registermode_chart(data);
    },

    initCheckinPieChart: function(data)
    {
        this.chart_donut.init_checkin_pie_chart(data);
    },

    initVisitsPerApChart: function(data)
    {
        this.chart_donut_ap_access.init_ap_access(data);
    },

    initVisitsPerApNetworkTabChart: function(data)
    {
        this.chart_donut_ap_access.init_ap_access_network_tab(data);
    },

    initRecordsPerApChart: function(data)
    {
        this.chart_donut_ap_access.init_ap_records_access(data);
    },

    initWSBBlockedCategoriesChart: function(data)
    {
        this.chart_donut_ap_access.init_wsb_blocked_categories(data);
    },

    initWSBMostAccessedCategoriesChart: function(data)
    {
        this.chart_donut_ap_access.init_wsb_most_accessed_categories(data);
    },

    initOverviewBars: function(data)
    {
        this.chart_ordered_bars.init_overview(data);
    },

    initDownloadUploadChart: function(data)
    {
        this.chart_lines_fill_nopoints.init(data);
    },

    initDownloadUploadChartDefault: function(data)
    {
        this.chart_lines_fill_nopoints_month.init(data);
    },

    initAccessChart: function(data)
    {
        this.chart_simple.init_access(data);
    },

    initViewsChart: function(data)
    {
        this.chart_donut_campaign_views.init(data);
    },

    initVisitsChart: function(data)
    {
        this.chart_simple.init(data);
    },

    initVisitsChartTest: function(data)
    {
        this.chart_simple.init_test(data);
    },

    initSignUpsByHoursChart: function(data)
    {
        this.chart_signups_by_hours.init(data);
    },

    // utility class
    utility:
    {
        chartColors: [ themerPrimaryColor, "#444", "#777", "#999", "#DDD", "#336699" ],
        chartBackgroundColors: ["#fff", "#fff"],

        applyStyle: function(that)
        {
            that.options.colors = charts.utility.chartColors;
            that.options.grid.backgroundColor = { colors: charts.utility.chartBackgroundColors };
            that.options.grid.borderColor = charts.utility.chartColors[0];
            that.options.grid.color = charts.utility.chartColors[0];
        },

        randNum: function()
        {
            return (Math.floor( Math.random()* (1+40-20) ) ) + 20;
        }
    },

    // ordered bars chart
    chart_ordered_bars:
    {
        // chart data
        data: null,

        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            series: {
                bars: {
                    align: "center"
                }
            },
            xaxis: {
                mode: "categories",
                tickLength: 0
            },
            yaxis: {
                minTickSize: 1,
                tickDecimals: 0
            },
            grid: {
                show: true,
                aboveData: true,
                color: "#3f3f3f" ,
                labelMargin: 5,
                axisMargin: 0,
                borderWidth: 0,
                borderColor: null,
                minBorderMargin: 5,
                clickable: true,
                hoverable: true,
                autoHighlight: false,
                mouseActiveRadius: 20,
                backgroundColor : { }
            },
            tooltip: true,
            tooltipOpts: {
                content: "%y.0",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            },
            valueLabels: {
                show: true
            }
        },

        // initialize
        init: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);

            var data = [
                {
                    label: 'Visitas',
                    data: chart_data.signIns,
                    bars: {
                        show: true,
                        barWidth: 0.6,
                        order: 2
                    }
                },
                {
                    label: 'Cadastros',
                    data: chart_data.signUps,
                    bars: {
                        show: true,
                        barWidth: 0.6,
                        order: 1
                    }
                }
            ];

            this.plot = $.plot($("#chart_ordered_bars"), data, this.options);
        },

        init_overview: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);

            var ds = new Array();
            ds.push({
                data: chart_data.reverse()
            });

            this.data = ds;

            this.plot = $.plot($("#chart_ordered_bars_overview"), this.data, this.options);
        }

    },

    // donut chart
    chart_donut:
    {
        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            series: {
                pie: {
                    show: true,
                    innerRadius: 0.4,
                    highlight: {
                        opacity: 0.1
                    },
                    radius: 1,
                    stroke: {
                        color: '#fff',
                        width: 8
                    },
                    startAngle: 2,
                    combine: {
                        color: '#EEE',
                        threshold: 0.05,
                        label: 'Outros'
                    },
                    label: {
                        show: true,
                        radius: 1,
                        formatter: function(label, series){
                            return '<div class="label label-inverse">'+label+'&nbsp;'+Math.round(series.percent)+'%</div>';
                        }
                    }
                },
                grow: {	active: false}
            },
            legend:{ show:false },
            grid: {
                hoverable: true,
                clickable: true,
                backgroundColor : { }
            },
            colors: [],
            tooltip: true,
            tooltipOpts: {
                content: "%s : %y.1"+"%",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            }
        },

        // initialize
        init: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);
            this.plot = $.plot($("#chart_donut"), chart_data, this.options);
        },

        init_registermode_chart: function(chart_data)
        {
            $("#chart_registermode > img").remove();

            if(chart_data.length > 0){
                $("#chart_registermode").html('');
                charts.utility.applyStyle(this);
                this.plot = $.plot($("#chart_registermode"), chart_data, this.options);
            }
        },

        init_checkin_pie_chart: function(chart_data)
        {
            $("#chart_pie_checkin > img").remove();

            if(chart_data.length > 0){
                $("#chart_pie_checkin").html('');
                charts.utility.applyStyle(this);
                this.plot = $.plot($("#chart_pie_checkin"), chart_data, this.options);
            }
        },

        init_devices_chart: function(chart_data)
        {
            $("#chart_devices > img").remove();

            if(chart_data.length > 0){
                $("#chart_devices").html('');
                charts.utility.applyStyle(this);
                this.plot = $.plot($("#chart_devices"), chart_data, this.options);
            }
        },

        init_platform_chart: function(chart_data)
        {
            $("#chart_platforms > img").remove();

            if(chart_data.length > 0){
                $("#chart_platforms").html('');
                charts.utility.applyStyle(this);
                this.plot = $.plot($("#chart_platforms"), chart_data, this.options);
            }
        },

        init_ap_access: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);
            this.plot = $.plot($("#chart_donut_visits"), chart_data, this.options);
        }
    },

    chart_donut_ap_access:
    {
        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            series: {
                pie: {
                    show: true,
                    innerRadius: 0.4,
                    highlight: {
                        opacity: 0.1
                    },
                    radius: 1,
                    stroke: {
                        color: '#fff',
                        width: 8
                    },
                    startAngle: 2,
                    combine: {
                        color: '#EEE',
                        threshold: 0,
                        label: 'Outros'
                    },
                    label: {
                        show: true,
                        radius: 1,
                        formatter: function(label, series){
                            return '<div class="label label-inverse">'+label+'&nbsp;'+Math.round(series.percent)+'%</div>';
                        }
                    }
                },
                grow: {	active: false}
            },
            legend:{show:false},
            grid: {
                hoverable: true,
                clickable: true,
                backgroundColor : { }
            },
            colors: [],
            tooltip: true,
            tooltipOpts: {
                content: "%s : %y.1"+"%",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            }
        },

        init_ap_access_network_tab: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);
            this.plot = $.plot($("#chart_donut"), chart_data.signIns, this.options);
        },

        init_ap_access: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);
            this.plot = $.plot($("#chart_donut_visits"), chart_data.signIns, this.options);
        },

        init_ap_records_access: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);
            this.plot = $.plot($("#chart_donut_records"), chart_data.signUps, this.options);
        },

        init_wsb_blocked_categories: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);
            this.plot = $.plot($("#chart_donut_blocked_categories"), chart_data.blockedCategories, this.options);
        },

        init_wsb_most_accessed_categories: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);
            this.plot = $.plot($("#chart_donut_most_accessed_categories"), chart_data.mostAccessedCategories, this.options);
        },
    },

    chart_donut_campaign_views:
    {
        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            series: {
                pie: {
                    show: true,
                    innerRadius: 0,
                    highlight: {
                        opacity: 0.1
                    },
                    radius: 1,
                    stroke: {
                        color: '#F2F2F2',
                        width: 12
                    },
                    startAngle: 2,
                    combine: {
                        color: '#EEE',
                        threshold: 0,
                        label: 'Outras'
                    },
                    label: {
                        show: true,
                        radius: 1,
                        formatter: function(label, series){
                            return '<div class="label label-inverse" alt="' + label + '" title="' + label + '">' +
                                Math.round(series.percent) + '%</div>';
                        }
                    }
                },
                grow: {	active: false}
            },
            legend:{ show:true },
            grid: {
                hoverable: true,
                clickable: true,
                backgroundColor : { }
            },
            colors: [],
            tooltip: true,
            tooltipOpts: {
                content: "%s : %y.1"+"%",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            }
        },

        init: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);
            this.plot = $.plot($("#chart_donut_campaign_views"), chart_data.campaignViews, this.options);
        }
    },

    chart_download_upload_lines:
    {
        // chart data
        data:
        {
            d1: [],
            d2: []
        },

        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            grid: {
                show: true,
                aboveData: true,
                color: "#3f3f3f",
                labelMargin: 5,
                axisMargin: 0,
                borderWidth: 0,
                borderColor:null,
                minBorderMargin: 5 ,
                clickable: true,
                hoverable: true,
                autoHighlight: true,
                mouseActiveRadius: 20,
                backgroundColor : { }
            },
            series: {
                grow: {active:false},
                lines: {
                    show: true,
                    fill: true,
                    lineWidth: 2,
                    steps: false
                },
                points: {show:false}
            },
            legend: { position: "nw" },
            yaxis: { min: 0 },
            xaxis: {ticks:11, tickDecimals: 0},
            colors: [],
            shadowSize:1,
            tooltip: true,
            tooltipOpts: {
                content: "%s : %y.0",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            }
        },

        // initialize
        init: function()
        {
            // apply styling
            charts.utility.applyStyle(this);

            // generate some data
            this.data.d1 = [["12/01", 3+charts.utility.randNum()], ["13/01", 6+charts.utility.randNum()], ["14/01", 9+charts.utility.randNum()], [4, 12+charts.utility.randNum()],[5, 15+charts.utility.randNum()],[6, 18+charts.utility.randNum()],[7, 21+charts.utility.randNum()],[8, 15+charts.utility.randNum()],[9, 18+charts.utility.randNum()],[10, 21+charts.utility.randNum()],[11, 24+charts.utility.randNum()],[12, 27+charts.utility.randNum()],[13, 30+charts.utility.randNum()],[14, 33+charts.utility.randNum()],[15, 24+charts.utility.randNum()],[16, 27+charts.utility.randNum()],[17, 30+charts.utility.randNum()],[18, 33+charts.utility.randNum()],[19, 36+charts.utility.randNum()],[20, 39+charts.utility.randNum()],[21, 42+charts.utility.randNum()],[22, 45+charts.utility.randNum()],[23, 36+charts.utility.randNum()],[24, 39+charts.utility.randNum()],[25, 42+charts.utility.randNum()],[26, 45+charts.utility.randNum()],[27,38+charts.utility.randNum()],[28, 51+charts.utility.randNum()],[29, 55+charts.utility.randNum()], [30, 60+charts.utility.randNum()]];
            this.data.d2 = [[1, charts.utility.randNum()-5], [2, charts.utility.randNum()-4], [3, charts.utility.randNum()-4], [4, charts.utility.randNum()],[5, 4+charts.utility.randNum()],[6, 4+charts.utility.randNum()],[7, 5+charts.utility.randNum()],[8, 5+charts.utility.randNum()],[9, 6+charts.utility.randNum()],[10, 6+charts.utility.randNum()],[11, 6+charts.utility.randNum()],[12, 2+charts.utility.randNum()],[13, 3+charts.utility.randNum()],[14, 4+charts.utility.randNum()],[15, 4+charts.utility.randNum()],[16, 4+charts.utility.randNum()],[17, 5+charts.utility.randNum()],[18, 5+charts.utility.randNum()],[19, 2+charts.utility.randNum()],[20, 2+charts.utility.randNum()],[21, 3+charts.utility.randNum()],[22, 3+charts.utility.randNum()],[23, 3+charts.utility.randNum()],[24, 2+charts.utility.randNum()],[25, 4+charts.utility.randNum()],[26, 4+charts.utility.randNum()],[27,5+charts.utility.randNum()],[28, 2+charts.utility.randNum()],[29, 2+charts.utility.randNum()], [30, 3+charts.utility.randNum()]];

            // make chart
            this.plot = $.plot(
                '#chart_download_upload_lines', [
                    {
                        label: "Download",
                        data: this.data.d1,
                        lines: {fillColor: "#fff8f2"},
                        points: {fillColor: "#88bbc8"}
                    },
                    {
                        label: "Upload",
                        data: this.data.d2,
                        lines: {fillColor: "rgba(0,0,0,0.1)"},
                        points: {fillColor: "#ed7a53"}
                    }
                ],
                this.options
            );
        }
    },

    // lines chart with fill & without points
    chart_lines_fill_nopoints:
    {
        // chart data
        data:
        {
            d1: [],
            d2: []
        },

        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            grid: {
                show: true,
                aboveData: true,
                color: "#3f3f3f",
                labelMargin: 5,
                axisMargin: 0,
                borderWidth: 0,
                borderColor:null,
                minBorderMargin: 5 ,
                clickable: true,
                hoverable: true,
                autoHighlight: true,
                mouseActiveRadius: 20,
                backgroundColor : { }
            },
            series: {
                grow: {
                    active: true
                },
                lines: {
                    show: true,
                    fill: true,
                    lineWidth: 1,
                    steps: false
                },
                points: {show:true}
            },
            legend: { position: "nw" },
            yaxis: { min: 0 },
            xaxis: {
                mode:"time",
                timeformat: "%d/%m"
            },
            colors: [],
            shadowSize:1,
            tooltip: true,
            tooltipOpts: {
                content: "%s: %y.0 MB",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            }
        },

        // initialize
        init: function(chart_data)
        {
            charts.utility.applyStyle(this);

            this.data.d1 = chart_data.download;
            this.data.d2 = chart_data.upload;

            this.plot = $.plot(
                '#chart_lines_fill_nopoints',
                [
                    {
                        label: "Download",
                        data: this.data.d1,
                        lines: {fillColor: "#fff8f2"},
                        points: {fillColor: "#88bbc8"},
                        xaxis: 'string'
                    },
                    {
                        label: "Upload",
                        data: this.data.d2,
                        lines: {fillColor: "rgba(0,0,0,0.1)"},
                        points: {fillColor: "#ed7a53"},
                        xaxis: 'string'
                    }
                ],
                this.options
            );
        }
    },

    chart_lines_fill_nopoints_month:
    {
        // chart data
        data:
        {
            d1: [],
            d2: []
        },

        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            grid: {
                show: true,
                aboveData: true,
                color: "#3f3f3f",
                labelMargin: 5,
                axisMargin: 0,
                borderWidth: 0,
                borderColor:null,
                minBorderMargin: 5 ,
                clickable: true,
                hoverable: true,
                autoHighlight: true,
                mouseActiveRadius: 20,
                backgroundColor : { }
            },
            series: {
                grow: {
                    active: true
                },
                lines: {
                    show: true,
                    fill: true,
                    lineWidth: 1,
                    steps: false
                },
                points: {show:true}
            },
            legend: { position: "nw" },
            yaxis: { min: 0 },
            xaxis: {
                ticks: [
                    [1,'Janeiro'],
                    [2,'Fevereiro'],
                    [3,'Março'],
                    [4,'Abril'],
                    [5,'Maio'],
                    [6,'Junho'],
                    [7,'Julho'],
                    [8,'Agosto'],
                    [9,'Setembro'],
                    [10,'Outubro'],
                    [11,'Novembro'],
                    [12,'Dezembro']
                ]
            },
            colors: [],
            shadowSize:1,
            tooltip: true,
            tooltipOpts: {
                content: "%s : %y.0 MB",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            }
        },

        // initialize
        init: function(chart_data)
        {
            charts.utility.applyStyle(this);

            this.data.d1 = chart_data.download;
            this.data.d2 = chart_data.upload;

            this.plot = $.plot(
                '#chart_lines_fill_nopoints',
                [
                    {
                        label: "Download",
                        data: this.data.d1,
                        lines: {fillColor: "#fff8f2"},
                        points: {fillColor: "#88bbc8"},
                        xaxis: 'string'
                    },
                    {
                        label: "Upload",
                        data: this.data.d2,
                        lines: {fillColor: "rgba(0,0,0,0.1)"},
                        points: {fillColor: "#ed7a53"},
                        xaxis: 'string'
                    }
                ],
                this.options
            );
        }
    },

    // simple chart
    chart_simple:
    {
        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            grid:
            {
                show: true,
                aboveData: true,
                color: "#3f3f3f",
                labelMargin: 5,
                axisMargin: 0,
                borderWidth: 0,
                borderColor:null,
                minBorderMargin: 5,
                clickable: true,
                hoverable: true,
                autoHighlight: true,
                mouseActiveRadius: 20,
                backgroundColor : { },
                mode: "time"
            },
            series: {
                grow: {active: false},
                lines: {
                    show: true,
                    fill: false,
                    lineWidth: 4,
                    steps: false
                },
                points: {
                    show:true,
                    radius: 5,
                    symbol: "circle",
                    fill: true,
                    borderColor: "#fff"
                }
            },
            //legend: { position: "se" },
            legend: false,
            colors: [],
            shadowSize:1,
            tooltip: true, //activate tooltip
            tooltipOpts: {
                content: "%s : %y",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            },
            xaxis: {
                tickDecimals: 0,
                mode: "categories",
                tickLength: 0
            },
            yaxis: {
                minTickSize: 1,
                tickDecimals: 0
            }
        },

        // initialize
        init_access: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);

            this.plot = $.plot(
                $("#chart_simple"),
                [{
                    label: "Acessos",
                    data: chart_data.signIns.sort(),
                    lines: {fillColor: "#DA4C4C"},
                    points: {fillColor: "#fff"}
                }], this.options);
        },

        // initialize
        init: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);

            this.plot = $.plot(
                $("#chart_simple"),
                [{
                    label: "Visitas",
                    data: chart_data.signIns,
                    lines: {fillColor: "#DA4C4C"},
                    points: {fillColor: "#fff"}
                }], this.options);
        },

        // initialize
        init_test: function(chart_data)
        {
            charts.utility.applyStyle(this);

            this.plot = $.plot(
                $("#visits_per_day"),
                [{
                    label: "Visitas",
                    data: chart_data.reverse(),
                    lines: {fillColor: "#DA4C4C"},
                    points: {fillColor: "#fff"}
                }], this.options);
        },

        // initialize
        init_views_pre_login_banner_detail: function(chart_data)
        {
            // apply styling
            charts.utility.applyStyle(this);

            this.plot = $.plot(
                $("#chart_simple"),
                [{
                    label: "Visualizações",
                    data: chart_data.preLoginBanner.sort(),
                    lines: {fillColor: "#DA4C4C"},
                    points: {fillColor: "#fff"}
                }], this.options);
        }

    },

    chart_signups_by_hours:
    {
        // will hold the chart object
        plot: null,

        // chart options
        options:
        {
            grid:
            {
                show: true,
                aboveData: true,
                color: "#3f3f3f",
                labelMargin: 5,
                axisMargin: 0,
                borderWidth: 0,
                borderColor:null,
                minBorderMargin: 5,
                clickable: true,
                hoverable: true,
                autoHighlight: true,
                mouseActiveRadius: 20,
                backgroundColor : { },
                mode: "time"
            },
            series: {
                grow: {active: false},
                lines: {
                    show: true,
                    fill: false,
                    lineWidth: 4,
                    steps: false
                },
                points: {
                    show:true,
                    radius: 5,
                    symbol: "circle",
                    fill: true,
                    borderColor: "#fff"
                }
            },
            //legend: { position: "se" },
            legend: false,
            colors: [],
            shadowSize:1,
            tooltip: true, //activate tooltip
            tooltipOpts: {
                content: "%s : %y",
                shifts: {
                    x: -30,
                    y: -50
                },
                defaultTheme: false
            },
            xaxis: {
                tickDecimals: 0,
                mode: "categories",
                tickLength: 0
            },
            yaxis: {
                minTickSize: 1,
                tickDecimals: 0
            }
        },

        // initialize
        init: function(chart_data)
        {

            // apply styling
            charts.utility.applyStyle(this);

            this.plot = $.plot(
                $("#signups_by_hours"),
                [{
                    label: "Cadastros",
                    data: chart_data.signUps.sort(),
                    lines: {fillColor: "#DA4C4C"},
                    points: {fillColor: "#fff"}
                }], this.options);
        }
    }
};