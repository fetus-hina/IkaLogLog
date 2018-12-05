/*! Copyright (C) 2018 AIZAWA Hina | MIT License */

window.workListConfig = () => {
  const storage = window.localStorage;
  const loadAllConfig = () => {
    const json = storage.getItem("work-list");
    const config = json ? JSON.parse(json) : {};
    const defaults = {
      "hscroll": false,
      "cell-splatnet": true,
      "cell-map": true,
      "cell-special": false,
      "cell-result": true,
      "cell-golden": true,
      "cell-golden-wave": false,
      "cell-power": true,
      "cell-power-wave": false,
      "cell-danger-rate": true,
      "cell-title": true,
      "cell-title-after": false,
      "cell-datetime": true,
      "cell-reltime": false,
    };
    for (let i in defaults) {
      if (defaults.hasOwnProperty(i)) {
        if (config[i] === undefined) {
          config[i] = defaults[i];
        }
      }
    }
    return config;
  };
  const loadConfig = key => {
    const config = loadAllConfig();
    return config[key];
  };
  const updateConfig = (key, enable) => {
    const config = loadAllConfig();
    config[key] = !!enable;
    storage.setItem("work-list", JSON.stringify(config));
  };
  const changeTableHScroll = enable => {
    if (enable) {
      $(".table-responsive").addClass("table-responsive-force");
    } else {
      $(".table-responsive").removeClass("table-responsive-force");
    }
  };
  const changeCellVisibility = (klass, enable) => {
    if (enable) {
      $("." + klass).show();
    } else {
      $("." + klass).hide();
    }
  };
  const loadConfigAndUpdateUI = () => {
    $("#table-hscroll").each(function() {
      const enable = loadConfig("hscroll");
      $(this).prop("checked", enable);
      changeTableHScroll(enable);
    });
    $(".table-config-chk").each(function() {
      const klass = $(this).attr("data-klass");
      const enable = loadConfig(klass);
      $(this).prop("checked", enable);
      changeCellVisibility(klass, enable);
    });
  };
  loadConfigAndUpdateUI();
  $("#table-hscroll").click(function() {
    const enable = $(this).prop("checked");
    changeTableHScroll(enable);
    updateConfig("hscroll", enable);
  });
  $(".table-config-chk").click(function() {
    const klass = $(this).attr("data-klass");
    const enable = $(this).prop("checked");
    changeCellVisibility(klass, enable);
    updateConfig(klass, enable);
  });
  $(window).on("storage", () => {
    loadConfigAndUpdateUI();
  });
};
