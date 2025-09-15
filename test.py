import bcrypt

# password in chiaro
password_raw = "pwd123"

# genera l'hash (equivalente a password_hash in PHP con PASSWORD_DEFAULT)
hashed = bcrypt.hashpw(password_raw.encode('utf-8'), bcrypt.gensalt())

print("Password originale:", password_raw)
print("Hash generato:", hashed.decode('utf-8'))
