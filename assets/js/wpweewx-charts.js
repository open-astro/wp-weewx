(function () {
  if (typeof window === 'undefined' || !window.Chart) {
    return;
  }

  var charts = document.querySelectorAll('.weewx-weather__chart-canvas');
  if (!charts.length) {
    return;
  }

  function getChartColor(root, className) {
    if (!root) {
      root = document.documentElement;
    }
    var styles = window.getComputedStyle(root);
    switch (className) {
      case 'secondary':
        return styles.getPropertyValue('--weewx-chart-series-secondary').trim() || '#64748b';
      case 'tertiary':
        return styles.getPropertyValue('--weewx-chart-series-tertiary').trim() || '#f59e0b';
      case 'quaternary':
        return styles.getPropertyValue('--weewx-chart-series-quaternary').trim() || '#ef4444';
      case 'primary':
      default:
        return styles.getPropertyValue('--weewx-chart-series-primary').trim() || '#1d4ed8';
    }
  }

  function hexToRgba(hex, alpha) {
    if (!hex) {
      return hex;
    }
    var normalized = hex.replace('#', '').trim();
    if (normalized.length === 3) {
      normalized = normalized.split('').map(function (c) { return c + c; }).join('');
    }
    if (normalized.length !== 6) {
      return hex;
    }
    var r = parseInt(normalized.slice(0, 2), 16);
    var g = parseInt(normalized.slice(2, 4), 16);
    var b = parseInt(normalized.slice(4, 6), 16);
    return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
  }

  function withAlpha(color, alpha) {
    if (!color) {
      return color;
    }
    if (color.indexOf('rgb(') === 0) {
      return color.replace('rgb(', 'rgba(').replace(')', ', ' + alpha + ')');
    }
    if (color.indexOf('rgba(') === 0) {
      return color.replace(/rgba\(([^,]+),([^,]+),([^,]+),[^\)]+\)/, 'rgba($1,$2,$3,' + alpha + ')');
    }
    if (color.indexOf('#') === 0) {
      return hexToRgba(color, alpha);
    }
    return color;
  }

  charts.forEach(function (canvas) {
    var dataEl = canvas.parentNode ? canvas.parentNode.querySelector('.weewx-weather__chart-data') : null;
    if (!dataEl) {
      return;
    }

    var payload;
    try {
      payload = JSON.parse(dataEl.textContent || '{}');
    } catch (error) {
      return;
    }

    if (!payload || !Array.isArray(payload.datasets) || !payload.datasets.length) {
      return;
    }

    var root = canvas.closest('.weewx-weather');
    var type = payload.type || 'line';
    var datasets = payload.datasets.map(function (dataset) {
      var color = getChartColor(root, dataset.class || 'primary');
      var datasetType = dataset.type || type;
      var isSingle = payload.datasets.length === 1;

      var config = {
        data: Array.isArray(dataset.data) ? dataset.data : [],
        borderColor: color,
        backgroundColor: 'transparent',
        borderWidth: 2,
        pointRadius: 0,
        pointHoverRadius: 3,
        tension: 0.35,
        spanGaps: true
      };

      if (datasetType === 'bar') {
        config.backgroundColor = withAlpha(color, 0.35);
        config.borderWidth = 0;
        config.barPercentage = 0.6;
        config.categoryPercentage = 0.8;
      } else if (datasetType === 'radar') {
        config.backgroundColor = withAlpha(color, 0.18);
        config.borderWidth = 2;
        config.pointRadius = 2;
      } else if (isSingle) {
        config.backgroundColor = withAlpha(color, 0.12);
        config.fill = true;
      }

      if (dataset.type) {
        config.type = dataset.type;
      }

      return config;
    });

    var styles = window.getComputedStyle(root || document.documentElement);
    var tickColor = styles.getPropertyValue('--weewx-muted').trim() || '#566173';
    var gridColor = styles.getPropertyValue('--weewx-border').trim() || '#e5e7eb';

    var options = {
      responsive: true,
      maintainAspectRatio: false,
      animation: true,
      plugins: {
        legend: { display: false },
        tooltip: { enabled: true }
      },
      scales: type === 'radar' ? {} : {
        x: {
          display: true,
          ticks: {
            color: tickColor,
            autoSkip: true,
            maxTicksLimit: 3
          },
          grid: { display: false }
        },
        y: {
          display: true,
          ticks: {
            color: tickColor,
            maxTicksLimit: 3
          },
          grid: {
            color: gridColor
          },
          min: typeof payload.yMin === 'number' ? payload.yMin : undefined,
          max: typeof payload.yMax === 'number' ? payload.yMax : undefined
        }
      },
      elements: {
        point: { radius: 0 }
      }
    };

    if (type === 'radar') {
      options.scales = {
        r: {
          angleLines: { color: gridColor },
          grid: { color: gridColor },
          pointLabels: { color: tickColor },
          ticks: { color: tickColor, maxTicksLimit: 4 }
        }
      };
    }

    new window.Chart(canvas.getContext('2d'), {
      type: type,
      data: {
        labels: Array.isArray(payload.labels) ? payload.labels : [],
        datasets: datasets
      },
      options: options
    });
  });
})();

(function () {
  var toggles = document.querySelectorAll('[data-unit-toggle]');
  if (!toggles.length) {
    return;
  }

  function setCookie(name, value, days) {
    var maxAge = days * 24 * 60 * 60;
    document.cookie = name + '=' + value + ';path=/;max-age=' + maxAge;
  }

  toggles.forEach(function (toggle) {
    toggle.addEventListener('click', function (event) {
      var button = event.target.closest('[data-unit]');
      if (!button) {
        return;
      }
      var unit = button.getAttribute('data-unit');
      if (!unit) {
        return;
      }
      setCookie('wpweewx_temp_unit', unit, 365);
      window.location.reload();
    });
  });
})();
