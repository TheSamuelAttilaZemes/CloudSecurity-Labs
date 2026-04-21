# Part 3: First Login to Keycloak, Realm Creation, Users, Groups, and Clients

## 1. Overview

This part creates the identity structure needed for the lab.

At the end of this part there should be:

* a new realm named `cloudlab`
* at least two example users
* a group used for protected application access
* two OIDC clients for the two OAuth2 Proxy containers
* client secrets copied back into `docker-compose.yml`



## 2. First Login to the Admin Console

Open in a browser:

```text
https://localhost:8443/keycloak/
```

Use the bootstrap admin credentials from `.env`:

* username: value of `KEYCLOAK_ADMIN_USER`
* password: value of `KEYCLOAK_ADMIN_PASSWORD`

Important point:

The built-in `master` realm is used for Keycloak administration itself.
Do not build the application identities for this lab directly in `master`.



## 3. Create a New Realm

Create a new realm with this name:

* `cloudlab`

Why use a separate realm?

* it separates application identities from Keycloak platform administration
* it gives the lab its own isolated identity space
* it makes the client and user model easier to understand



## 4. Add Example Users

Create two users in the `cloudlab` realm.

Suggested example accounts:

* `alice`
* `bob`

For each user:

1. create the user
2. set a permanent password
3. confirm the user is enabled

You can choose different names if preferred, but keep them consistent throughout the lab.



## 5. Why Two Users Are Useful

Two users make later access-control tests easier to observe.

For example:

* one user can be placed in the allowed group
* the other can be left outside it temporarily

That allows later tests to distinguish:

* authentication success
* authorization success



## 6. Create a Group for Protected Access

Create a group in the `cloudlab` realm:

* `lab3-users`

Add at least one of the users to that group.

Suggested pattern:

* add `alice` to `lab3-users`
* leave `bob` outside the group initially



## 7. Groups, Roles, and Clients

At this stage, focus on the following concepts:

* **users**: individual identities
* **groups**: collections of users
* **roles**: named permissions or privileges
* **clients**: registered applications or relying parties

This lab uses groups as the first access-control mechanism because they make the rule easy to describe:

**membership in `lab3-users` means access should be allowed to the protected example applications**



## 8. Create the First OIDC Client

Create a client for `oauth2-proxy-a`.

Suggested values:

* Client type: `OpenID Connect`
* Client ID: `whoami-a-proxy`
* Client authentication: enabled
* Standard flow: enabled
* Direct access grants: disabled

Valid redirect URI:

* `https://localhost:8443/oauth2/a/callback`

Web origin:

* `https://localhost:8443`

The redirect URI matters because OAuth2 Proxy will later send the browser there after the login flow. If the URI in Keycloak does not match what OAuth2 Proxy uses, the authentication flow will fail.



## 9. Create the Second OIDC Client

Create a second client for `oauth2-proxy-b`.

Suggested values:

* Client type: `OpenID Connect`
* Client ID: `whoami-b-proxy`
* Client authentication: enabled
* Standard flow: enabled
* Direct access grants: disabled

Valid redirect URI:

* `https://localhost:8443/oauth2/b/callback`

Web origin:

* `https://localhost:8443`



## 10. Read the Client Secrets

After saving each client, open the credentials or client-secret view and copy the generated secret.

You will need these two values:

* secret for `whoami-a-proxy`
* secret for `whoami-b-proxy`

Replace the placeholders in `docker-compose.yml` with those values.

Example pattern:

```yaml
      - --client-secret=PASTE_REAL_SECRET_HERE
```



## 11. Add Group Information to Tokens

For group-based authorization through OAuth2 Proxy, group information needs to be available as part of the identity information returned by Keycloak.

A simple approach is to add a group-membership mapper to each client.

Suggested mapper idea:

* mapper type: Group Membership
* token claim name: `groups`
* add to ID token: enabled
* add to access token: enabled
* add to userinfo: enabled

This matters because OAuth2 Proxy cannot apply a group-based rule unless it can see the group information for the authenticated user.



## 12. Add Audience Information

OAuth2 Proxy also behaves more predictably when the token audience matches what the client expects.

Add an audience mapper so that the client ID is included as an audience value where required.

This is a useful detail to explain because identity systems often fail not because login is broken, but because token claims do not line up with what downstream components expect.



## 13. Areas of the Keycloak Console Worth Reviewing

At this stage, explore these areas in the `cloudlab` realm:

* Users
* Groups
* Clients
* Client scopes
* Roles
* Sessions
* Realm settings



## 14. Start the New Protected-App Components After Updating Secrets

Once the two client secrets are pasted into `docker-compose.yml`, start the remaining new containers:

```bash
docker compose up -d whoami-a whoami-b oauth2-proxy-a oauth2-proxy-b
```

Then confirm they are running:

```bash
docker compose ps
docker compose logs oauth2-proxy-a --tail=50
docker compose logs oauth2-proxy-b --tail=50
```



## 15. Exercises

1. Explain why the `cloudlab` realm is created instead of using `master`.
2. Explain the difference between a user, a group, a role, and a client in Keycloak.
3. Explain why the redirect URI must match the OAuth2 Proxy callback path exactly.
4. Explain why group claims need to be included for group-based authorization to work.
