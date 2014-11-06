define(['modules/add-repository'], function(addRepository) {
    addRepository.users.attach();
    addRepository.repos.attach();
});
