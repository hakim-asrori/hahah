<?php $this->load->view('common/html_start') ?>
<!-- u can add more plugin here (between start and body) -->
<script>
  // cek session
  if (window.sessionStorage) {
    var idUser = sessionStorage.getItem("sessionIdUser");
    var name = sessionStorage.getItem("sessionName");
    var token = sessionStorage.getItem("sessionToken");
    if (idUser == null) location.href = '<?= site_url('login') ?>';
  }
</script>
<?php $this->load->view('common/html_body') ?>
<div class="wrapper ">
  <!-- Side bar here -->
  <?php $this->load->view('template/sidebar') ?>
  <!-- Content here -->
  <div class="main-panel" id="main-panel">
    <?php $this->load->view('template/navbar-monitoring') ?>

    <!-- <div style="position:absolute; right:30px; top:20px; z-index : ff411e9;">
            <button class="btn btn-success" id="btn_play">PLAY</button>
            <button class="btn btn-primary" id="btn_stop">STOP</button>
        </div> -->
    <!-- Content -->
    <div class="panel-header panel-header-lg" style="margin-bottom: -300px">
    </div>

    <div class="content">
      <div class="row" style="margin-bottom : 50px">
        <div class="col-lg-12">
          <div id="realtimeAllData"></div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-3 col-md-6">
          <div class="card card-chart">
            <div class="card-header">
              <h5 class="card-category">Sensor</h5>
              <h4 class="card-title">Turbidity (NTU)</h4>
            </div>
            <div class="card-body">
              <div class="chart-area">
                <canvas id="realtimeChartTurbidity"></canvas>
              </div>
            </div>
            <div class="card-footer">
              <div class="stats">
                <i class="now-ui-icons ui-2_time-alarm"></i> Update in a second
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-chart">
            <div class="card-header">
              <h5 class="card-category">Sensor</h5>
              <h4 class="card-title">PH</h4>
            </div>
            <div class="card-body">
              <div class="chart-area">
                <canvas id="realtimeChartPH"></canvas>
              </div>
            </div>
            <div class="card-footer">
              <div class="stats">
                <i class="now-ui-icons ui-2_time-alarm"></i> Update in a second
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-chart">
            <div class="card-header">
              <h5 class="card-category">Sensor</h5>
              <h4 class="card-title">Temperature (&#8451;)</h4>
            </div>
            <div class="card-body">
              <div class="chart-area">
                <canvas id="realtimeChartTemperature"></canvas>
              </div>
            </div>
            <div class="card-footer">
              <div class="stats">
                <i class="now-ui-icons ui-2_time-alarm"></i> Update in a second
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="card card-chart">
            <div class="card-header">
              <h5 class="card-category">Sensor</h5>
              <h4 class="card-title">Water Level (cm)</h4>
            </div>
            <div class="card-body">
              <div class="chart-area">
                <canvas id="realtimeChartWaterLevel"></canvas>
              </div>
            </div>
            <div class="card-footer">
              <div class="stats">
                <i class="now-ui-icons ui-2_time-alarm"></i> Update in a second
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
    <!-- /End Content -->

    <?php $this->load->view('template/footer') ?>
  </div>
</div>
<?php $this->load->view('common/html_footer') ?>
<!-- u can add more plugin footer here (between start and body) -->
<script src="<?= site_url('assets/js/plugins/mqttws31.min.js') ?>"></script>
<script src="<?= site_url('assets/js/plugins/plotly-latest.min.js') ?>"></script>
<script src="<?= site_url('assets/js/plugins/Gauge.js') ?>"></script>
<script src="<?= site_url('assets/js/plugins/sweetalert2.js') ?>"></script>
<script>
  var topicSubscribe;
  var device = 0;
  var turbidity = [];
  var temperature = [];
  var ph = [];
  var water_level = [];
  var rawdata = [];

  var time = new Date();
</script>
<script>
  // Create a client instance
  let client = new Paho.MQTT.Client("broker.hivemq.com", 8000, "sama00user");

  // set callback handlers
  client.onConnectionLost = onConnectionLost;
  client.onMessageArrived = onMessageArrived;

  // connect the client
  client.connect({
    onSuccess: onConnect,
    onFailure: onFailure
  });

  // called when the client connects
  function onConnect() {
    // Once a connection has been made, make a subscription and send a message.
    console.log("onConnect");
  }

  function onFailure(msg) {
    console.log(msg)
    Swal.fire('MQTT Connection', 'Gagal terhubung ke broker', 'error')
  }

  // called when the client loses its connection
  function onConnectionLost(responseObject) {
    if (responseObject.errorCode !== 0) {
      console.log("onConnectionLost:" + responseObject.errorMessage);
    }
    Swal.fire('MQTT Connection', 'Koneksi terputus', 'warning')
  }

  // called when a message arrives
  function onMessageArrived(message) {
    console.log("onMessageArrived:" + message.payloadString);
    updateAllDataChart(message.payloadString)
  }
</script>
<script>
  device = $('#list_devices').val();

  $.get('<?= $this->config->item('api_url') ?>management/device/' + sessionStorage.getItem('sessionIdUser'), function(data) {
    console.log(data)
    data.data.devices.forEach(function(v, k) {
      $('#list_devices').append(`<option value="${v.idDevice}">${v.aquariumName}</option>`);
    })
  })

  $('#list_devices').change(function(e) {
    //alert($(this).val());
    device = $(this).val();
    if (topicSubscribe != null) client.unsubscribe(topicSubscribe);
    topicSubscribe = device;
    client.subscribe(topicSubscribe);
    // turbidity 		= [];
    // temperature		= [];
    // ph 		        = [];
    // water_level     = [];
    // rawdata         = [];
    Plotly.newPlot('realtimeAllData', rawdata);
  })
</script>
<script>
  rawdata = [{
      name: 'Turbidity',
      x: [time],
      y: turbidity,
      mode: 'lines',
      line: {
        color: '#80CAF6' //, shape : 'spline'
      },
      type: 'scatter'
    },
    {
      name: 'PH',
      x: [time],
      y: ph,
      mode: 'lines',
      line: {
        color: '#1BCB1B' //, shape : 'spline'
      },
      type: 'scatter'
    },
    {
      name: 'Temperature',
      x: [time],
      y: temperature,
      mode: 'lines',
      line: {
        color: '#FFA500' //, shape : 'spline'
      },
      type: 'scatter'
    },
    {
      name: 'Water Level',
      x: [time],
      y: water_level,
      mode: 'lines',
      line: {
        color: '#1C678B' //, shape : 'spline'
      },
      type: 'scatter'
    }
  ]

  Plotly.plot('realtimeAllData', rawdata);

  function updateAllDataChart(data) {
    var s = data.split('#');
    var tur = s[0];
    var ph = s[1];
    var temp = s[2];
    var wl = s[3];

    updateGaugeTurbidity(tur);
    updateGaugePH(ph);
    updateGaugeTemperature(temp);
    updateGaugeWaterLevel(wl);


    var time = new Date();

    var update = {
      x: [
        [time],
        [time],
        [time],
        [time]
      ],
      y: [
        [tur],
        [ph],
        [temp],
        [wl]
      ]
    }

    var olderTime = time.setMinutes(time.getMinutes() - 1);
    var futureTime = time.setMinutes(time.getMinutes() + 1);

    var minuteView = {
      xaxis: {
        type: 'date',
        range: [olderTime, futureTime]
      }
    }

    Plotly.relayout('realtimeAllData', minuteView);
    Plotly.extendTraces('realtimeAllData', update, [0, 1, 2, 3])
  }
</script>

<script>
  var ctxTurbidity = document.getElementById("realtimeChartTurbidity").getContext("2d");
  var ctxPH = document.getElementById("realtimeChartPH").getContext("2d");
  var ctxTemperature = document.getElementById("realtimeChartTemperature").getContext("2d");
  var ctxWaterLevel = document.getElementById("realtimeChartWaterLevel").getContext("2d");


  var insCtxTurbidity = new Chart(ctxTurbidity, {
    type: "tsgauge",
    data: {
      datasets: [{
        backgroundColor: ["#fd9704", "#0fdc63", "#ff7143"],
        borderWidth: 0,
        gaugeData: {
          value: 0,
          valueColor: "#ff411e"
        },
        gaugeLimits: [0, 1000, 3000, 4000]
      }]
    },
    options: {
      events: [],
      showMarkers: true
    }
  });

  var insCtxPH = new Chart(ctxPH, {
    type: "tsgauge",
    data: {
      datasets: [{
        backgroundColor: ["#f54254", "#0fdc63", "#9842f5"],
        borderWidth: 0,
        gaugeData: {
          value: 0,
          valueColor: "#ff411e"
        },
        gaugeLimits: [0, 6, 8, 14]
      }]
    },
    options: {
      events: [],
      showMarkers: true
    }
  });

  var insCtxTemperature = new Chart(ctxTemperature, {
    type: "tsgauge",
    data: {
      datasets: [{
        backgroundColor: ["#22A4F1", "#0fdc63", "#ff7143"],
        borderWidth: 0,
        gaugeData: {
          value: 0,
          valueColor: "#ff411e"
        },
        gaugeLimits: [0, 22, 27, 50]
      }]
    },
    options: {
      events: [],
      showMarkers: true
    }
  });

  var insCtxWaterLevel = new Chart(ctxWaterLevel, {
    type: "tsgauge",
    data: {
      datasets: [{
        backgroundColor: ["#0fdc63", "#fd9704", "#ff7143"],
        borderWidth: 0,
        gaugeData: {
          value: 0,
          valueColor: "#ff411e"
        },
        gaugeLimits: [0, 5, 10, 20]
      }]
    },
    options: {
      events: [],
      showMarkers: true
    }
  });

  function updateGaugeTurbidity(value) {
    //ctxTurbidity.config.data.datasets[0].gaugeLimits = [0,1,2,3,4,5];
    insCtxTurbidity.config.data.datasets[0].gaugeData.value = parseFloat(value);
    var color = ''
    if (value <= 1000) {
      color = "#fd9704"
    } else if (value <= 3000) {
      color = "#0fdc63";
    } else if (value <= 4000) {
      color = "#ff7143";
    }
    insCtxTurbidity.data.datasets[0].gaugeData.valueColor = color;
    insCtxTurbidity.update();
  }

  function updateGaugePH(value) {
    //ctxTurbidity.config.data.datasets[0].gaugeLimits = [0,1,2,3,4,5];
    insCtxPH.config.data.datasets[0].gaugeData.value = parseFloat(value);
    var color = ''
    if (value <= 6) {
      color = "#f54254";
    } else if (value <= 8) {
      color = "#0fdc63";
    } else if (value <= 14) {
      color = "#9842f5";
    }
    insCtxPH.data.datasets[0].gaugeData.valueColor = color;
    insCtxPH.update();
  }

  function updateGaugeTemperature(value) {
    //ctxTurbidity.config.data.datasets[0].gaugeLimits = [0,1,2,3,4,5];
    insCtxTemperature.config.data.datasets[0].gaugeData.value = parseFloat(value);
    var color = ''
    if (value < 22) {
      color = "#22A4F1";
    } else if (value <= 27) {
      color = "#0fdc63";
    } else if (value <= 50) {
      color = "#ff7143";
    }
    insCtxTemperature.data.datasets[0].gaugeData.valueColor = color;
    insCtxTemperature.update();
  }

  function updateGaugeWaterLevel(value) {
    //ctxTurbidity.config.data.datasets[0].gaugeLimits = [0,1,2,3,4,5];
    insCtxWaterLevel.config.data.datasets[0].gaugeData.value = parseFloat(value);
    var color = ''
    if (value <= 5) {
      color = "#0fdc63";
    } else if (value <= 10) {
      color = "#fd9704";
    } else if (value <= 20) {
      color = "#ff7143";
    }
    insCtxWaterLevel.data.datasets[0].gaugeData.valueColor = color;
    insCtxWaterLevel.update();
  }
</script>

<script>
  $('#btn_play').click(function() {
    if (device == 0) {
      Swal.fire('Choose device', 'please choose device before', 'question')
    }
    message = new Paho.MQTT.Message("1");
    message.destinationName = device + "/ASKING";
    client.send(message);
  })

  $('#btn_stop').click(function() {
    if (device == 0) {
      Swal.fire('Choose device', 'please choose device before', 'question')
    }
    message = new Paho.MQTT.Message("0");
    message.destinationName = device + "/ASKING";
    client.send(message);
  })
</script>

<script>
  $('#sidebar-logout, #navbar-logout').click(function(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Logout ?',
      text: "Are you sure ?",
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#ff411e',
      cancelButtonColor: '#bbb',
      confirmButtonText: 'Logout'
    }).then((result) => {
      if (result.value) {
        if (window.sessionStorage) {
          sessionStorage.removeItem("sessionIdUser");
          sessionStorage.removeItem("sessionName");
          sessionStorage.removeItem("sessionToken");
        }

        location.href = '<?= site_url('login') ?>';
      }
    })
  })
</script>

<?php $this->load->view('common/html_end') ?>