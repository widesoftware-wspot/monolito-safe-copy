import App from "./App";

/**
 * Fix para usar o routing do symfony dentro do typescript
 */
declare var Routing : any;

let config = {
    urlLoadTemplateFields : Routing.generate('first_config_load_template_field'),
    urlSaveConfigurations : Routing.generate('first_config_save_fields'),
    urlDashboardIndex : Routing.generate('admin_dashboard')
};

const app : App = new App(config);
app.init();

