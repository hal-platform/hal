define([], function() {
    return [
        { url: "/",                                controller: "dashboard" },
        { url: "/environments/reorder",            controller: "environment-manage" },
        { url: "/repositories/add",                controller: "repository-add" },
        { url: "/repositories/:id:/status",        controller: "repository-status" },
        { url: "/repositories/:id:/deployments",   controller: "repository-deployment-add" },
        { url: "/queue",                           controller: "queue" },
        { url: "/repositories/:id:/build",         controller: "build-create" },
        { url: "/build/:id:",                      controller: "build" },
        { url: "/push/:id:",                       controller: "push" }
    ];
});
