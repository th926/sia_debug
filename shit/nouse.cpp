class BindWrapper {
public:
    MYSQL_BIND bind;
    std::string m_replacee;
    std::string m_replacer;
    int current_aid;
    std::string m_built_statement;
    BindWrapper(enum enum_field_types type) {
        memset(&bind, 0, sizeof(MYSQL_BIND));
        bind.buffer_type = type;
        bind.is_null = 0;
    }
    void set_replacement_thing(const char* param, const char* replace_string) {
        m_replacee = param;
        m_replacer = replace_string;
    }
    void replace(int aid) {
        if (m_replacee.empty()) exit(-1);
        current_aid = aid;
        std::string tmp = std::to_string(current_aid);
        m_built_statement = m_replacee;
        m_built_statement.replace(m_built_statement.find(m_replacer), m_replacer.length(), m_replacer);
        bind.buffer = m_built_statement.data();
        bind.length = (unsigned long*)m_built_statement.length();
    }
};
