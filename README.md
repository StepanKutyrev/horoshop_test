# User REST API

# Setup
# make build
# make up

# API Testing (Root Token: root, User1: secret, User2: pass)

# 1. Root: Create a new user
curl -X POST http://localhost:8080/v1/api/users -H "Authorization: Bearer root" -H "Content-Type: application/json" -d '{"login": "user3", "phone": "123", "pass": "pass3"}'

# 2. User: Create a user (403)
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8080/v1/api/users -H "Authorization: Bearer pass" -H "Content-Type: application/json" -d '{"login": "fail", "phone": "000", "pass": "fail"}'

# 3. Root: Get any user data
curl -X GET http://localhost:8080/v1/api/users/3 -H "Authorization: Bearer root"

# 4. User: Get own data
curl -X GET http://localhost:8080/v1/api/users/4 -H "Authorization: Bearer pass"

# 5. User: Try to get other user data (403)
curl -s -o /dev/null -w "%{http_code}\n" -X GET http://localhost:8080/v1/api/users/3 -H "Authorization: Bearer pass"

# 6. User: Update own data
curl -X PUT http://localhost:8080/v1/api/users/4 -H "Authorization: Bearer pass" -H "Content-Type: application/json" -d '{"id": 4, "login": "user4b", "phone": "999", "pass": "pass"}'

# 7. User: Update other user (403)
curl -s -o /dev/null -w "%{http_code}\n" -X PUT http://localhost:8080/v1/api/users/3 -H "Authorization: Bearer pass" -H "Content-Type: application/json" -d '{"id": 3, "login": "hacked", "phone": "000", "pass": "hacked"}'

# 8. Validation: Use > 8 chars (400)
curl -X POST http://localhost:8080/v1/api/users -H "Authorization: Bearer root" -H "Content-Type: application/json" -d '{"login": "longloginname", "phone": "123", "pass": "p"}'

# 9. User: Delete own record (403)
curl -s -o /dev/null -w "%{http_code}\n" -X DELETE http://localhost:8080/v1/api/users/4 -H "Authorization: Bearer pass"

# 10. Root: Delete user (204)
curl -s -o /dev/null -w "%{http_code}\n" -X DELETE http://localhost:8080/v1/api/users/3 -H "Authorization: Bearer root"

# Run all automated tests
# make test

# Shutdown
# make down
