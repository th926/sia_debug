#pragma once
#include <mysql.h>
#include <string>
#include "HELPME.hpp"

#define PREFIX_REPLACE_STRING "?"
#define PREFIX_REPLACE_LENGTH 1
class SearchQuery {
public:
    std::string m_query;
    MYSQL_BIND *m_binds = null;
    const char **m_name = null;
    MYSQL_STMT *m_stmt = null;

    SearchQuery(MYSQL *connection, MYSQL_BIND *bind, const char**name)
    : m_stmt(mysql_stmt_init(connection)), m_binds(bind), m_name(name)
    {
        mysql_stmt_prepare(m_stmt, m_query.c_str(), m_query.length());
        if (m_binds != null)
            mysql_stmt_bind_named_param(m_stmt, m_binds, 1, m_name);
    }
    void query(const char *prefix, const char *query) {
        if (query == null || prefix == null)
            exit(-2);
        m_query = query;
        m_query.replace(
            m_query.find_first_of(PREFIX_REPLACE_STRING),
            PREFIX_REPLACE_LENGTH,
            prefix
        );
    }
    [[deprecated]]
    void bind(MYSQL_BIND *bind, const char**name) {
        m_binds = bind;
        m_name = name;
        if (m_binds != null)
            mysql_stmt_bind_named_param(m_stmt, m_binds, 1, m_name);
    }
    bool execute() {
        int status = 0;
        mysql_stmt_execute(m_stmt); // This needs to returnmaxx
        status = mysql_stmt_fetch(m_stmt);
        if (status == 1)
            exit(-3);
        if (status == MYSQL_NO_DATA)
            return false;
        return true;
    }
    ~SearchQuery() {
        if (m_stmt != null) mysql_stmt_close(m_stmt);
    }
};
