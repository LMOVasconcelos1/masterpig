Nova inscrição no MasterPig

CNPJ: {{ $payload['cnpj'] ?? '-' }}
Banco: {{ $payload['database'] ?? '-' }}
Usuário do banco: {{ $payload['db_user'] ?? '-' }}

Nome: {{ $payload['nome'] ?? '-' }}
E-mail: {{ $payload['email'] ?? '-' }}
CPF: {{ $payload['cpf'] ?? '-' }}
Usuário: {{ $payload['usuario'] ?? '-' }}
Perfil: {{ $payload['perfil'] ?? '-' }}

Data/Hora: {{ $payload['created_at'] ?? '-' }}

