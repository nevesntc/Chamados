-- Run as the migration/administrative role after creating chamados_runtime with a private password.
-- Reapply after migrations that add tables or change required write operations.
GRANT CONNECT ON DATABASE postgres TO chamados_runtime;
GRANT USAGE ON SCHEMA chamados TO chamados_runtime;
GRANT SELECT ON ALL TABLES IN SCHEMA chamados TO chamados_runtime;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA chamados TO chamados_runtime;

REVOKE INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA chamados FROM chamados_runtime;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA chamados
    REVOKE INSERT, UPDATE, DELETE ON TABLES FROM chamados_runtime;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA chamados
    GRANT SELECT ON TABLES TO chamados_runtime;
ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA chamados
    GRANT USAGE, SELECT ON SEQUENCES TO chamados_runtime;

GRANT INSERT, UPDATE ON chamados.users TO chamados_runtime;
-- UPDATE em workspaces: o dono renomeia a equipe.
GRANT INSERT, UPDATE ON chamados.workspaces TO chamados_runtime;
-- DELETE em workspace_members: desligar alguém, ou a própria pessoa sair.
GRANT INSERT, DELETE ON chamados.workspace_members TO chamados_runtime;
GRANT INSERT, UPDATE, DELETE ON chamados.workspace_invites TO chamados_runtime;
GRANT INSERT, UPDATE ON chamados.assignees TO chamados_runtime;
GRANT INSERT, UPDATE ON chamados.tickets TO chamados_runtime;
GRANT UPDATE ON chamados.ticket_write_locks TO chamados_runtime;
GRANT INSERT, UPDATE, DELETE ON chamados.sessions TO chamados_runtime;
-- Os limites de tentativa só valem entre requisições se o cache for persistente.
GRANT INSERT, UPDATE, DELETE ON chamados.cache TO chamados_runtime;
GRANT INSERT, UPDATE, DELETE ON chamados.cache_locks TO chamados_runtime;
