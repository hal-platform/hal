## Domain Model

![Domain Model](images/domain.png)

### Entities

Resource       | Description
-------------- | -----------
Organization   | An arbitrary organization or collection of applications
Application    | Deployable application with a single source vcs repository for code
Environment    | An environment such as **staging** or **production**
Template       | A deployment target template
Target         | A deployment target (bucket, server)
User           | A client user of the system

### Actions

Resource       | Description
-------------- | -----------
Build          | **Action** - Compiled application code from a VCS snapshot
Push           | **Action** - Code deployment to a target
Job Event      | An event recording during an action such as Build or Push
