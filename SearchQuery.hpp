#pragma once
#include <mysql.h>
#include <string>
#include "HELPME.hpp"

class SearchQuery {
public:
    std::string m_query;
    MYSQL_BIND *binds = null;
    const char **name = null;
    MYSQL_STMT *m_stmt = null;
    std::string *r_prefix = null;

    SearchQuery(MYSQL *connection, std::string *prefix, const char *query)
    : m_query(query), m_stmt(mysql_stmt_init(connection)), r_prefix(prefix)
    {
        mysql_stmt_prepare(m_stmt, m_query.c_str(), m_query.length());
        this->prefix();
    }
    void bind_stmt() {
        if (binds != null)
            mysql_stmt_bind_named_param(m_stmt, binds, 1, name);
    }
    bool execute() {
        mysql_stmt_execute(m_stmt); // This needs to returnmaxx
        return true;
    }
    ~SearchQuery() {
        if (m_stmt) mysql_stmt_close(m_stmt);
    }
    void prefix() {
        if (binds.amount != 0 && r_prefix != null)
            binds.add_bind(r_prefix->c_str(), r_prefix->size(), MYSQL_TYPE_STRING);
    }
};
