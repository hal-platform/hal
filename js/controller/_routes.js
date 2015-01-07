define([], function() {
    return [
        { url: "/environments/reorder",             controller: "environment-manage" },

        { url: "/repositories/add",                 controller: "repository-add" },
        { url: "/repositories/{id}/deployments",    controller: "deployment-add" },

        { url: "/repositories/{id}/build",          controller: "build-create" },
        { url: "/builds/{id}/push",                 controller: "push-create" },

        { url: "/",                                 controller: "dashboard",                    component: "queue-dashboard" },
        { url: "/queue",                            controller: "queue",                        component: "queue-dashboard" },

        { url: "/repositories/{id}/status",         controller: "repository-status",            component: "job-updater" },
        { url: "/builds/{id}",                      controller: "build",                        component: "job-updater" },
        { url: "/pushes/{id}",                      controller: "push",                         component: "job-updater" },
        { url: "/repositories/{id}/builds/:page*:", controller: "builds",                       component: "job-updater" },
        { url: "/repositories/{id}/pushes/:page*:", controller: "pushes",                       component: "job-updater" }
    ];
});
