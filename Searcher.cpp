#include <mysqlx/xdevapi.h>
#include "Searcher.h"

Searcher::Searcher(mysqlx::Table &table, int attachment_id)
    : table(table), attachment_id(attachment_id) {}
    inline std::string Searcher::caiq(std::string first = "%", std::string last = "%") {
        return first + std::to_string(attachment_id) + last;
    }
    inline std::vector<int> Searcher::flatten(mysqlx::RowResult &rowres) {
        std::vector<int> retvec;

        mysqlx::Row row;
        while ((row = rowres.fetchOne()))
        {
          retvec.push_back((int)row[0]);
        }
        return retvec;
    }

    std::vector<int> OptionSearch::widgets() {
        mysqlx::RowResult res = table.select("option_id").where("option_name LIKE :w AND option_value LIKE :a_id").bind("w", "widget_%%").bind("a_id", caiq()).execute();
        return flatten(res);
    }
    std::vector<int> OptionSearch::customizer_usage() {
        mysqlx::RowResult res = table.select("option_id").where("option_name LIKE :tms AND option_value LIKE :a_id").bind("tms", "theme_mods_%%").bind("a_id", caiq()).execute();
        return flatten(res);
    }
    std::vector<int> OptionSearch::options() {
        mysqlx::RowResult res = table.select("option_id").where("option_value LIKE :a_id").bind("a_id", caiq()).execute();
        return flatten(res);
    }

    std::vector<int> PostmetaSearch::featured_image() {
        mysqlx::RowResult res = table.select("post_id").where("meta_key = :t_id AND meta_value = :a_id").bind("t_id", "_thumbnail_id").bind("a_id", attachment_id).execute();
        return flatten(res);
    }
    std::vector<int> PostmetaSearch::acf() {
        mysqlx::RowResult res = table.select("post_id").where("meta_value = :first OR meta_value = :second OR meta_value = :third and meta_key NOT LIKE :fourth")
            .bind("first", caiq("%\"", "\"%"))
            .bind("second", caiq("%i:"))
            .bind("third", caiq("%attachment_id\";i:", ""))
            .bind("fourth", "_%%")
            .execute();
        return flatten(res);
    }


    std::vector<int> PostsSearch::content() {
        mysqlx::RowResult res = table.select("ID").where("post_status = publish AND (post_content LIKE :first OR post_content LIKE :second OR post_content LIKE :third")
        .bind("first", caiq("%wp-image-"))
        .bind("second", caiq("%attachwmend_id=\"", "\"%"))
        .bind("third", caiq("%data-id=\"", "\"%"))
        .execute();
        return flatten(res);
    }
    std::vector<int> PostsSearch::acf_blocks() {
        mysqlx::RowResult res = table.select("ID").where("post_type != revision and post_type != attachment AND post_content LIKE :a_id")
        .bind("a_id", caiq("%\":", ",%"))
        .execute();
        return flatten(res);
    }
